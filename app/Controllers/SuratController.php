<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Models\SuratModel;
use App\Security\AuditLogger;
use App\Services\DriveUploadException;
use App\Services\DriveConfigurationException;
use App\Services\GoogleDriveService;
use App\Services\SuratService;
use DomainException;

final class SuratController
{
    private SuratService $surat;

    public function __construct()
    {
        $pdo = Connection::get();
        $this->surat = new SuratService(new SuratModel($pdo), new AuditLogger($pdo));
    }

    public function index(): void
    {
        AuthMiddleware::requireLogin();
        $filters = [
            'q' => mb_substr(trim($this->queryString('q')), 0, 200),
            'dari' => $this->filterDate($this->queryString('dari')),
            'sampai' => $this->filterDate($this->queryString('sampai')),
        ];
        $pageValue = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
        $result = $this->surat->search($filters, $pageValue !== false && $pageValue > 0 ? $pageValue : 1);
        render('surat/index', [
            'pageTitle' => 'Surat Masuk',
            'filters' => $filters,
            'data' => $result['items'],
            'pagination' => $result,
        ]);
    }

    public function create(): void
    {
        AuthMiddleware::requireLogin();
        $errors = [];
        $formData = $this->surat->emptyForm();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            requirePost();
            validateCsrf();
            [$formData, $errors] = $this->surat->validate($_POST);
            if (!$errors) {
                $id = $this->surat->create($formData);
                $this->uploadDocumentIfProvided($id);
                redirect('surat');
            }
        }

        render('surat/create', [
            'pageTitle' => 'Tambah Surat',
            'errors' => $errors,
            'formData' => $formData,
            'surat' => null,
            'sifatOptions' => SuratService::SIFAT,
        ]);
    }

    public function show(): void
    {
        AuthMiddleware::requireLogin();
        $id = $this->requestId();
        $surat = $this->surat->find($id);
        if (!$surat) {
            http_response_code(404);
            exit('Data tidak ditemukan.');
        }

        render('surat/show', [
            'pageTitle' => 'Detail Surat',
            'surat' => $surat,
            'id' => $id,
            'driveUrl' => $this->surat->safeDriveUrl($surat['link_drive'] ?? null),
            'notice' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash']);
    }

    public function edit(): void
    {
        AuthMiddleware::requireLogin();
        $id = $this->requestId();
        $surat = $this->surat->find($id);
        if (!$surat) {
            http_response_code(404);
            exit('Data tidak ditemukan.');
        }

        $errors = [];
        $formData = $this->surat->formFromRecord($surat);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            requirePost();
            validateCsrf();
            [$formData, $errors] = $this->surat->validate($_POST);
            if (!$errors) {
                $this->surat->update($id, $formData);
                $this->uploadDocumentIfProvided($id);
                redirect('surat/' . $id);
            }
        }

        render('surat/edit', [
            'pageTitle' => 'Edit Surat',
            'surat' => $surat,
            'id' => $id,
            'errors' => $errors,
            'formData' => $formData,
            'sifatOptions' => SuratService::SIFAT,
        ]);
    }

    public function delete(): void
    {
        AuthMiddleware::requireLogin();
        requirePost();
        validateCsrf();
        $id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id !== false && $id !== null && $id > 0) {
            $this->surat->delete($id);
        }
        redirect('surat');
    }

    private function requestId(): int
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

        return $id !== false && $id !== null && $id > 0 ? $id : 0;
    }

    private function uploadDocumentIfProvided(int $suratId): void
    {
        $upload = $_FILES['document'] ?? null;
        if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $userId = (int) currentUser()['id'];
        try {
            (new GoogleDriveService(Connection::get()))->upload($upload, $suratId, $userId);
        } catch (DomainException | DriveConfigurationException | DriveUploadException $error) {
            (new AuditLogger(Connection::get()))->record(
                'GOOGLE_DRIVE_UPLOAD_FAILED',
                'google_drive',
                $suratId,
                $userId,
                null,
                ['reason' => $error->getMessage()]
            );
            $_SESSION['flash'] = [
                'type' => 'warning',
                'message' => $error->getMessage(),
            ];
            redirect('surat/' . $suratId);
        }
    }

    private function queryString(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    private function filterDate(string $value): string
    {
        $date = trim($value);
        if ($date === '') {
            return '';
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($parsed === false || $parsed->format('Y-m-d') !== $date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return '';
        }

        return $date;
    }
}