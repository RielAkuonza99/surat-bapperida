<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;

final class SuratModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function dashboardSummary(): array
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) AS total, COUNT(tanggal_disposisi) AS disposisi FROM surat_masuk');
        $statement->execute();
        $summary = $statement->fetch();
        $latestStatement = $this->pdo->prepare('SELECT * FROM surat_masuk ORDER BY id DESC LIMIT 5');
        $latestStatement->execute();

        return [
            'total' => (int) $summary['total'],
            'disposisi' => (int) $summary['disposisi'],
            'terbaru' => $latestStatement->fetchAll(),
        ];
    }

    public function search(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        if ($filters['q'] !== '') {
            $conditions[] = '(uraian_pengusul LIKE :uraian OR keterangan LIKE :keterangan)';
            $parameters['uraian'] = '%' . $filters['q'] . '%';
            $parameters['keterangan'] = '%' . $filters['q'] . '%';
        }
        if ($filters['dari'] !== '') {
            $conditions[] = 'tanggal_masuk >= :dari';
            $parameters['dari'] = $filters['dari'];
        }
        if ($filters['sampai'] !== '') {
            $conditions[] = 'tanggal_masuk <= :sampai';
            $parameters['sampai'] = $filters['sampai'];
        }

        $sql = 'SELECT * FROM surat_masuk';
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY id DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM surat_masuk WHERE id = :id');
        $statement->execute(['id' => $id]);
        $surat = $statement->fetch();

        return $surat ?: null;
    }

    public function create(array $data): int
    {
        $this->pdo->beginTransaction();
        try {
            $sequenceStatement = $this->pdo->prepare('SELECT next_no FROM surat_sequences WHERE id = 1 FOR UPDATE');
            $sequenceStatement->execute();
            $number = $sequenceStatement->fetchColumn();
            if ($number === false) {
                throw new RuntimeException('Surat number sequence is not initialized.');
            }

            $updateSequence = $this->pdo->prepare('UPDATE surat_sequences SET next_no = next_no + 1 WHERE id = 1');
            $updateSequence->execute();

            $statement = $this->pdo->prepare('INSERT INTO surat_masuk (no, tanggal_masuk, tanggal_disposisi, uraian_pengusul, keterangan) VALUES (:no, :tanggal, :disposisi, :uraian, :keterangan)');
            $statement->execute([
                'no' => (int) $number,
                'tanggal' => $data['tanggal_masuk'],
                'disposisi' => $data['tanggal_disposisi'] ?: null,
                'uraian' => $data['uraian_pengusul'],
                'keterangan' => $data['keterangan'] ?: null,
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return $id;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE surat_masuk SET tanggal_masuk = :tanggal, tanggal_disposisi = :disposisi, uraian_pengusul = :uraian, keterangan = :keterangan WHERE id = :id');
        $statement->execute([
            'tanggal' => $data['tanggal_masuk'],
            'disposisi' => $data['tanggal_disposisi'] ?: null,
            'uraian' => $data['uraian_pengusul'],
            'keterangan' => $data['keterangan'] ?: null,
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM surat_masuk WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }
}