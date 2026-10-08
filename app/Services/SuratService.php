<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\SuratModel;
use App\Security\AuditLogger;

final class SuratService
{
    public const SIFAT = ['Sangat Segera', 'Segera', 'Rahasia'];
    private const DRIVE_HOSTS = ['drive.google.com', 'docs.google.com'];

    public function __construct(private SuratModel $surat, private AuditLogger $audit)
    {
    }

    public function dashboard(): array
    {
        return $this->surat->dashboardSummary();
    }

    public function search(array $filters, int $page = 1): array
    {
        return $this->surat->search($filters, $page);
    }

    public function find(int $id): ?array
    {
        return $id > 0 ? $this->surat->find($id) : null;
    }

    public function safeDriveUrl(?string $url): ?string
    {
        return $url !== null && $this->validDriveUrl($url) ? $url : null;
    }

    public function emptyForm(): array
    {
        return [
            'nama' => '',
            'judul' => '',
            'nomor_surat' => '',
            'tanggal_surat' => '',
            'tanggal_masuk' => date('Y-m-d'),
            'sifat' => '',
            'surat_dari' => '',
            'pengusul' => '',
            'uraian' => '',
            'keterangan' => '',
            'link_drive' => '',
            'disposisi' => array_fill_keys(
                SuratModel::DISPOSISI_TAHAP,
                ['asal' => '', 'tujuan' => '', 'tanggal' => '', 'keterangan' => '']
            ),
        ];
    }

    public function formFromRecord(array $surat): array
    {
        $form = $this->emptyForm();
        foreach (['nama', 'judul', 'nomor_surat', 'tanggal_surat', 'tanggal_masuk', 'sifat', 'surat_dari', 'pengusul', 'uraian', 'keterangan', 'link_drive'] as $field) {
            $form[$field] = (string) ($surat[$field] ?? $form[$field]);
        }
        if ($form['uraian'] === '' && $form['pengusul'] === '') {
            $form['uraian'] = (string) ($surat['uraian_pengusul'] ?? '');
        }

        foreach (SuratModel::DISPOSISI_TAHAP as $tahap) {
            $row = $surat['disposisi'][$tahap] ?? null;
            $form['disposisi'][$tahap] = [
                'asal' => (string) ($row['asal'] ?? ''),
                'tujuan' => (string) ($row['tujuan'] ?? ''),
                'tanggal' => (string) ($row['tanggal'] ?? ''),
                'keterangan' => (string) ($row['keterangan'] ?? ''),
            ];
        }

        return $form;
    }

    public function validate(array $input): array
    {
        $text = static function (string $key) use ($input): string {
            $value = $input[$key] ?? '';

            return is_scalar($value) ? trim((string) $value) : '';
        };
        $data = [
            'nama' => $text('nama'),
            'judul' => $text('judul'),
            'nomor_surat' => $text('nomor_surat'),
            'tanggal_surat' => $text('tanggal_surat'),
            'tanggal_masuk' => $text('tanggal_masuk'),
            'sifat' => $text('sifat'),
            'surat_dari' => $text('surat_dari'),
            'pengusul' => $text('pengusul'),
            'uraian' => $text('uraian'),
            'keterangan' => $text('keterangan'),
            'link_drive' => $text('link_drive'),
            'disposisi' => [],
        ];
        $errors = [];

        if (!$this->validDate($data['tanggal_masuk'])) {
            $errors[] = 'Tanggal diterima wajib berupa tanggal yang valid.';
        }
        if ($data['tanggal_surat'] !== '' && !$this->validDate($data['tanggal_surat'])) {
            $errors[] = 'Tanggal surat tidak valid.';
        }
        if ($data['judul'] === '' && $data['uraian'] === '') {
            $errors[] = 'Judul surat atau uraian wajib diisi.';
        }
        if (!in_array($data['sifat'], self::SIFAT, true)) {
            $errors[] = 'Pilih salah satu sifat surat yang tersedia.';
        }

        foreach (
            [
                ['nama', 150, 'Nama'],
                ['judul', 255, 'Judul surat'],
                ['nomor_surat', 100, 'Nomor surat'],
                ['surat_dari', 255, 'Surat dari'],
                ['pengusul', 255, 'Pengusul'],
            ] as [$field, $max, $label]
        ) {
            if (mb_strlen($data[$field]) > $max) {
                $errors[] = "{$label} maksimal {$max} karakter.";
            }
        }
        if (strlen($data['uraian']) > 65535 || strlen($data['keterangan']) > 65535) {
            $errors[] = 'Uraian dan keterangan tidak boleh melebihi batas penyimpanan.';
        }

        if ($data['link_drive'] !== '' && !$this->validDriveUrl($data['link_drive'])) {
            $errors[] = 'Link Google Drive harus berupa URL https dari drive.google.com atau docs.google.com.';
        }

        $previousDate = '';
        $submittedDisposisi = is_array($input['disposisi'] ?? null) ? $input['disposisi'] : [];
        foreach (SuratModel::DISPOSISI_TAHAP as $tahap) {
            $slot = is_array($submittedDisposisi[$tahap] ?? null) ? $submittedDisposisi[$tahap] : [];
            $row = [];
            foreach (['asal' => 150, 'tujuan' => 150, 'tanggal' => 10, 'keterangan' => 65535] as $field => $max) {
                $value = $slot[$field] ?? '';
                $row[$field] = is_scalar($value) ? trim((string) $value) : '';
                if ($field !== 'tanggal' && $field !== 'keterangan' && mb_strlen($row[$field]) > $max) {
                    $errors[] = "Disposisi {$tahap}: {$field} maksimal {$max} karakter.";
                }
                if ($field === 'keterangan' && strlen($row[$field]) > $max) {
                    $errors[] = "Keterangan disposisi {$tahap} terlalu panjang.";
                }
            }

            if ($row['tanggal'] !== '') {
                if (!$this->validDate($row['tanggal'])) {
                    $errors[] = "Tanggal disposisi {$tahap} tidak valid.";
                } elseif ($previousDate !== '' && $row['tanggal'] < $previousDate) {
                    $errors[] = "Tanggal disposisi {$tahap} tidak boleh lebih awal dari disposisi sebelumnya.";
                } else {
                    $previousDate = $row['tanggal'];
                }
            }
            $data['disposisi'][$tahap] = $row;
        }

        return [$data, $errors];
    }

    public function create(array $data): int
    {
        $id = $this->surat->create($data, (int) $_SESSION['user_id']);
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

    private function validDriveUrl(string $url): bool
    {
        if (strlen($url) > 500 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && in_array(strtolower((string) ($parts['host'] ?? '')), self::DRIVE_HOSTS, true)
            && !isset($parts['user'])
            && !isset($parts['pass']);
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
