<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = (string) config('DB_DRIVER', 'pgsql');
    if ($driver === 'pgsql' && !extension_loaded('pdo_pgsql')) {
        $driver = 'sqlite';
    }

    if ($driver === 'sqlite') {
        $path = BASE_PATH . '/' . ltrim((string) config('SQLITE_PATH', 'storage/geoflow.sqlite'), '/');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $path);
    } else {
        $host = (string) config('DB_HOST', '127.0.0.1');
        $port = (string) config('DB_PORT', '5432');
        $name = (string) config('DB_NAME', 'geo_system');
        $user = (string) config('DB_USER', 'geo_user');
        $password = (string) config('DB_PASSWORD', 'geo_password');
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);
        $pdo = new PDO($dsn, $user, $password);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

function db_driver(PDO $pdo): string
{
    return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
}
