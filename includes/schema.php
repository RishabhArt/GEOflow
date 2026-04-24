<?php

declare(strict_types=1);

function ensure_store_defaults(JsonStore $store): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $adminExists = $store->countWhere(
        'users',
        fn (array $user): bool => $user['username'] === config('DEFAULT_ADMIN_USER', 'admin')
    ) > 0;

    if (!$adminExists && env_bool('AUTO_SEED_ADMIN', true)) {
        $store->insert('users', [
            'username' => config('DEFAULT_ADMIN_USER', 'admin'),
            'password_hash' => password_hash((string) config('DEFAULT_ADMIN_PASSWORD', 'admin888'), PASSWORD_BCRYPT),
            'role' => 'admin',
            'created_at' => now_utc(),
            'updated_at' => now_utc(),
        ]);
    }

    $defaults = [
        'site_name' => config('SITE_NAME', 'GEOFlow'),
        'site_tagline' => 'Generative Engine Optimization content pipeline',
        'site_description' => 'Open-source GEO and SEO content production platform.',
        'theme' => 'aurora',
        'auto_publish' => config('AUTO_PUBLISH', 'false'),
    ];

    foreach ($defaults as $key => $value) {
        $exists = $store->countWhere('settings', fn (array $row): bool => $row['setting_key'] === $key) > 0;
        if (!$exists) {
            $store->insert('settings', [
                'setting_key' => $key,
                'setting_value' => (string) $value,
                'created_at' => now_utc(),
                'updated_at' => now_utc(),
            ]);
        }
    }

    $ready = true;
}
