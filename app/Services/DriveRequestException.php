<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class DriveRequestException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable,
        public readonly bool $mayHaveSucceeded = false,
        public readonly int $httpStatus = 0
    ) {
        parent::__construct($message);
    }
}
