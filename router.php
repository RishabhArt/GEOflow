<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? $uri : '/';
$path = __DIR__ . $uri;

if ($uri !== '/' && is_file($path)) {
    return false;
}

if (is_dir($path)) {
    $indexFile = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
    if (is_file($indexFile)) {
        require $indexFile;
        return true;
    }
}

require __DIR__ . '/index.php';
