<?php

declare(strict_types=1);

function boot_auth(JsonStore $store): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    if (!empty($_SESSION['auth_user_id'])) {
        $user = $store->find('users', (int) $_SESSION['auth_user_id']);
        if ($user) {
            $GLOBALS['geoflow_auth_user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
            ];
            return;
        }
    }

    $GLOBALS['geoflow_auth_user'] = null;
}

function current_user(): ?array
{
    return $GLOBALS['geoflow_auth_user'] ?? null;
}

function attempt_login(JsonStore $store, string $username, string $password): bool
{
    $user = $store->first('users', fn (array $row): bool => $row['username'] === $username);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['auth_user_id'] = $user['id'];
    $GLOBALS['geoflow_auth_user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];

    return true;
}

function logout_user(): void
{
    unset($_SESSION['auth_user_id']);
    $GLOBALS['geoflow_auth_user'] = null;
}

function require_admin(): void
{
    if (!current_user()) {
        redirect('/geo_admin/index.php?login=1');
    }
}
