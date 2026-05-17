<?php

use App\Kernel;

// When running under PHP's built-in dev server (php -S), serve existing static
// files (css/, js/, images, etc.) directly instead of routing them through
// Symfony. With nginx/apache + PHP-FPM this branch is skipped.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path !== '/' && is_file(__DIR__ . $path)) {
        return false;
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
