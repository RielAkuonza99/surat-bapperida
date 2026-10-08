<?php
declare(strict_types=1);

$baseUrl = (string) (getenv('APP_BASE_URL') ?: '');
if (trim($baseUrl) === '') {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
    $scriptName = str_replace('\\', '/', (string) $scriptName);

    if ($scriptName !== '') {
        $publicDir = rtrim(dirname($scriptName), '/');
        if ($publicDir !== '' && str_ends_with($publicDir, '/public')) {
            $publicDir = substr($publicDir, 0, -strlen('/public'));
        }
        if ($publicDir !== '' && $publicDir !== '/') {
            $baseUrl = $publicDir;
        }
    }
}

define('APP_ENVIRONMENT', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('APP_BASE_URL', rtrim($baseUrl, '/'));
ini_set('display_errors', APP_ENVIRONMENT === 'local' && APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');