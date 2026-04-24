<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$resource = $_GET['resource'] ?? 'meta';

try {
    if ($resource === 'articles') {
        echo json_encode([
            'data' => app('articles')->listPublished($_GET['category'] ?? null),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($resource === 'tasks') {
        echo json_encode([
            'data' => app('tasks')->listAll(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($resource === 'assets') {
        echo json_encode([
            'data' => app('assets')->listAll($_GET['type'] ?? null),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($resource === 'dashboard') {
        echo json_encode([
            'tasks' => app('tasks')->countsByStatus(),
            'articles' => app('articles')->countsByStatus(),
            'queue' => app('queue')->countsByStatus(),
            'assets' => app('assets')->statsByType(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'name' => 'GEOFlow API',
        'version' => '1.0.0',
        'resources' => [
            '/api/v1/?resource=articles',
            '/api/v1/?resource=tasks',
            '/api/v1/?resource=assets',
            '/api/v1/?resource=dashboard',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()]);
}
