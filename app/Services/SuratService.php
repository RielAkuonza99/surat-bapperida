<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\SuratModel;
use App\Security\AuditLogger;

final class SuratService
{
    public function __construct(private SuratModel $surat, private AuditLogger $audit)
    {
    }

    public function dashboard(): array
    {
        return $this->surat->dashboardSummary();
    }

    public function search(array $filters): array
    {
        return $this->surat->search($filters);
    }

    public function find(int $id): ?array
    {
        return $id > 0 ? $this->surat->find($id) : null;
    }

    public function validate(array $input): array
    {
        $data = [
            'tanggal_masuk' => trim((string) ($input['tanggal_masuk'] ?? '')),
            'tanggal_disposisi' => trim((string) ($input['tanggal_disposisi'] ?? '')),
            'uraian_pengusul' => trim((string) ($input['uraian_pengusul'] ?? '')),
            'keterangan' => trim((string) ($input['keterangan'] ?? '')),
        ];
        $errors = [];

        if (!$this->validDate($data['tanggal_masuk'])) {
            $errors[] = 'Tanggal masuk wajib berupa tanggal yang valid.';
        }
        if ($data['tanggal_disposisi'] !== '' && !$this->validDate($data['tanggal_disposisi'])) {
            $errors[] = 'Tanggal disposisi tidak valid.';
        }
        if ($data['uraian_pengusul'] === '') {
            $errors[] = 'Uraian / pengusul wajib diisi.';
        }
        if (strlen($data['uraian_pengusul']) > 65535 || strlen($data['keterangan']) > 65535) {
            $errors[] = 'Uraian dan keterangan tidak boleh melebihi batas penyimpanan.';
        }

        return [$data, $errors];
    }

    public function create(array $data): int
    {
        $id = $this->surat->create($data);
        $this->audit->record('CREATE', 'surat_masuk', $id, (int) $_SESSION['user_id']);

        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->surat->update($id, $data);
        $this->audit->record('UPDATE', 'surat_masuk', $id, (int) $_SESSION['user_id']);
    }

    public function delete(int $id): bool
    {
        $deleted = $this->surat->delete($id);
        if ($deleted) {
            $this->audit->record('DELETE', 'surat_masuk', $id, (int) $_SESSION['user_id']);
        }

        return $deleted;
    }

    private function validDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = \DateTimeImmutable::getLastErrors();

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }
}