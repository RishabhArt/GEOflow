<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/includes/env.php';
require_once BASE_PATH . '/includes/helpers.php';
require_once BASE_PATH . '/includes/store.php';
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/schema.php';
require_once BASE_PATH . '/includes/security.php';
require_once BASE_PATH . '/includes/lang.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/demo.php';
require_once BASE_PATH . '/includes/services/SettingsService.php';
require_once BASE_PATH . '/includes/services/AssetService.php';
require_once BASE_PATH . '/includes/services/ArticleService.php';
require_once BASE_PATH . '/includes/services/QueueService.php';
require_once BASE_PATH . '/includes/services/AIService.php';
require_once BASE_PATH . '/includes/services/TaskService.php';

load_env(BASE_PATH . '/.env');
date_default_timezone_set(env('TZ', 'Asia/Shanghai'));

if (env_bool('SECURITY_HEADERS', true) && PHP_SAPI !== 'cli') {
    apply_security_headers();
}

if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_name('geoflow_session');
    session_start();
}

$store = store();
ensure_store_defaults($store);

set_shared_container([
    'store' => $store,
    'settings' => new SettingsService($store),
    'assets' => new AssetService($store),
    'articles' => new ArticleService($store),
    'queue' => new QueueService($store),
    'ai' => new AIService($store),
    'tasks' => new TaskService($store),
]);

seed_demo_workspace();
boot_auth($store);
