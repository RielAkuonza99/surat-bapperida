<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Models\SuratModel;
use App\Security\AuditLogger;
use App\Services\SuratService;

final class SuratController
{
    private SuratService $surat;

    public function __construct()
    {
        $this->surat = new SuratService(new SuratModel(Connection::get()), new AuditLogger());
    }

    public function index(): void
    {
        AuthMiddleware::requireLogin();
        $filters = [
            'q' => substr(trim((string) ($_GET['q'] ?? '')), 0, 200),
            'dari' => $this->filterDate((string) ($_GET['dari'] ?? '')),
            'sampai' => $this->filterDate((string) ($_GET['sampai'] ?? '')),
        ];
        $data = $this->surat->search($filters);
        render('surat/index', ['pageTitle' => 'Surat Masuk', 'filters' => $filters, 'data' => $data]);
    }

    public function create(): void
    {
        AuthMiddleware::requireLogin();
        $errors = [];
        $formData = [
            'tanggal_masuk' => date('Y-m-d'),
            'tanggal_disposisi' => '',
            'uraian_pengusul' => '',
            'keterangan' => '',
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            requirePost();
            validateCsrf();
            [$formData, $errors] = $this->surat->validate($_POST);
            if (!$errors) {
                $this->surat->create($formData);
                redirect('surat-masuk.php');
            }
        }

        render('surat/create', ['pageTitle' => 'Tambah Surat', 'errors' => $errors, 'formData' => $formData]);
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

        render('surat/show', ['pageTitle' => 'Detail Surat', 'surat' => $surat, 'id' => $id]);
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
        $formData = [
            'tanggal_masuk' => $surat['tanggal_masuk'],
            'tanggal_disposisi' => $surat['tanggal_disposisi'] ?? '',
            'uraian_pengusul' => $surat['uraian_pengusul'],
            'keterangan' => $surat['keterangan'] ?? '',
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            requirePost();
            validateCsrf();
            [$formData, $errors] = $this->surat->validate($_POST);
            if (!$errors) {
                $this->surat->update($id, $formData);
                redirect('detail.php?id=' . $id);
            }
        }

        render('surat/edit', ['pageTitle' => 'Edit Surat', 'surat' => $surat, 'id' => $id, 'errors' => $errors, 'formData' => $formData]);
    }

    public function delete(): void
    {
        AuthMiddleware::requireLogin();
        requirePost();
        validateCsrf();
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id !== false && $id !== null && $id > 0) {
            $this->surat->delete($id);
        }
        redirect('surat-masuk.php');
    }

    private function requestId(): int
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

        return $id !== false && $id !== null && $id > 0 ? $id : 0;
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