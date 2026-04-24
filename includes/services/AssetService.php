<?php

declare(strict_types=1);

class AssetService
{
    public function __construct(private JsonStore $store)
    {
    }

    public function listAll(?string $type = null): array
    {
        $rows = $type
            ? $this->store->where('assets', fn (array $row): bool => $row['asset_type'] === $type)
            : $this->store->all('assets');

        usort($rows, fn (array $a, array $b): int => $b['id'] <=> $a['id']);
        return $rows;
    }

    public function create(array $data): int
    {
        return $this->store->insert('assets', [
            'asset_type' => $data['asset_type'],
            'title' => $data['title'],
            'content' => $data['content'],
            'meta_json' => $data['meta'] ?? [],
            'created_at' => now_utc(),
            'updated_at' => now_utc(),
        ]);
    }

    public function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->store->where('assets', fn (array $row): bool => in_array((int) $row['id'], $ids, true));
        usort($rows, fn (array $a, array $b): int => $b['id'] <=> $a['id']);
        return $rows;
    }

    public function statsByType(): array
    {
        $stats = [];
        foreach ($this->store->all('assets') as $asset) {
            $type = $asset['asset_type'];
            $stats[$type] = ($stats[$type] ?? 0) + 1;
        }
        ksort($stats);
        return $stats;
    }
}
