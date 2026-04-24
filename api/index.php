<?php

declare(strict_types=1);

$_SERVER['SCRIPT_NAME'] = '/api/index.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? $uri : '/';
$path = dirname(__DIR__) . $uri;

if ($uri !== '/' && is_file($path)) {
    return require $path;
}

if (is_dir($path)) {
    $indexFile = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
    if (is_file($indexFile)) {
        return require $indexFile;
    }
}

return require dirname(__DIR__) . '/index.php';

