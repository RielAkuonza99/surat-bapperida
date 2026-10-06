<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class SuratDocumentModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forSurat(int $suratId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, drive_api_id, drive_file_id, file_name, mime_type, file_size,
                    web_view_url, uploaded_at
             FROM surat_documents WHERE surat_id = :id ORDER BY uploaded_at DESC, id DESC'
        );
        $statement->execute(['id' => $suratId]);

        return $statement->fetchAll();
    }
}
