<?php
declare(strict_types=1);

define('APP_ENVIRONMENT', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
ini_set('display_errors', APP_ENVIRONMENT === 'local' && APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');