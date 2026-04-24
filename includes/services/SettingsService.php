<?php

declare(strict_types=1);

class SettingsService
{
    public function __construct(private JsonStore $store)
    {
    }

    public function all(): array
    {
        $rows = $this->store->all('settings');
        usort($rows, fn (array $a, array $b): int => strcmp($a['setting_key'], $b['setting_key']));

        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }

        return $result;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->store->first('settings', fn (array $row): bool => $row['setting_key'] === $key);
        return $row['setting_value'] ?? $default;
    }

    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $row = $this->store->first('settings', fn (array $row): bool => $row['setting_key'] === $key);
            if ($row) {
                $this->store->update((int) $row['id'], 'settings', [
                    'setting_value' => (string) $value,
                    'updated_at' => now_utc(),
                ]);
                continue;
            }

            $this->store->insert('settings', [
                'setting_key' => $key,
                'setting_value' => (string) $value,
                'created_at' => now_utc(),
                'updated_at' => now_utc(),
            ]);
        }
    }
}
