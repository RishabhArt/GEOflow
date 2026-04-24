<?php

declare(strict_types=1);

function set_shared_container(array $items): void
{
    $GLOBALS['geoflow_container'] = $items;
}

function app(string $key): mixed
{
    return $GLOBALS['geoflow_container'][$key] ?? null;
}

function config(string $key, mixed $default = null): mixed
{
    return env($key, $default);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . bin2hex(random_bytes(4));
}

function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}

function base_url(string $path = ''): string
{
    $siteUrl = rtrim((string) config('SITE_URL', 'http://localhost:18080'), '/');
    return $siteUrl . '/' . ltrim($path, '/');
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return is_string($uri) ? $uri : '/';
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function request_input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function language_options(): array
{
    return [
        'zh-CN' => 'Simplified Chinese',
        'en' => 'English',
        'ja' => 'Japanese',
        'es' => 'Español',
        'ru' => 'Русский',
    ];
}

function active_locale(): string
{
    $sessionLocale = $_GET['lang'] ?? ($_SESSION['locale'] ?? config('LOCALE', 'en'));
    if (!array_key_exists($sessionLocale, language_options())) {
        $sessionLocale = 'en';
    }

    if (PHP_SAPI !== 'cli') {
        $_SESSION['locale'] = $sessionLocale;
    }

    return $sessionLocale;
}

function render(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require BASE_PATH . '/includes/templates/' . $template . '.php';
}

function cli_log(string $message): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
    }
}

