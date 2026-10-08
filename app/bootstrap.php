<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$envFile = APP_ROOT . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile)) {
    $environment = @parse_ini_file($envFile, false, INI_SCANNER_RAW);
    if ($environment === false) {
        throw new RuntimeException('File .env tidak dapat dibaca. Periksa format setiap baris KEY=VALUE.');
    }

    foreach ($environment as $key => $value) {
        if (!is_string($key) || !preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || !is_string($value)) {
            throw new RuntimeException('File .env berisi nama variabel atau nilai yang tidak valid.');
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}
require_once APP_ROOT . '/app/Helpers/functions.php';
require_once APP_ROOT . '/config/app.php';
require_once APP_ROOT . '/config/security.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
