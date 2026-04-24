<?php

declare(strict_types=1);

class JsonStore
{
    private array $data;

    public function __construct(private string $path)
    {
        if (!is_file($this->path)) {
            $dir = dirname($this->path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($this->path, json_encode($this->emptyData(), JSON_PRETTY_PRINT));
        }

        $decoded = json_decode((string) file_get_contents($this->path), true);
        $this->data = is_array($decoded) ? $decoded : $this->emptyData();
        $this->data += $this->emptyData();
    }

    public function all(string $table): array
    {
        return array_values($this->data[$table] ?? []);
    }

    public function find(string $table, int $id): ?array
    {
        foreach ($this->all($table) as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }

    public function first(string $table, callable $filter): ?array
    {
        foreach ($this->all($table) as $row) {
            if ($filter($row)) {
                return $row;
            }
        }

        return null;
    }

    public function where(string $table, callable $filter): array
    {
        return array_values(array_filter($this->all($table), $filter));
    }

    public function countWhere(string $table, callable $filter): int
    {
        return count($this->where($table, $filter));
    }

    public function insert(string $table, array $row): int
    {
        $row['id'] = $this->nextId($table);
        $this->data[$table][] = $row;
        $this->persist();

        return (int) $row['id'];
    }

    public function update(int $id, string $table, array $changes): void
    {
        foreach ($this->data[$table] as $index => $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $this->data[$table][$index] = array_merge($row, $changes);
                $this->persist();
                return;
            }
        }
    }

    public function delete(int $id, string $table): void
    {
        $this->data[$table] = array_values(array_filter(
            $this->data[$table] ?? [],
            fn (array $row): bool => (int) ($row['id'] ?? 0) !== $id
        ));
        $this->persist();
    }

    private function nextId(string $table): int
    {
        $ids = array_column($this->data[$table] ?? [], 'id');
        return $ids ? (max($ids) + 1) : 1;
    }

    private function persist(): void
    {
        file_put_contents($this->path, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function emptyData(): array
    {
        return [
            'users' => [],
            'settings' => [],
            'ai_models' => [],
            'assets' => [],
            'tasks' => [],
            'jobs' => [],
            'articles' => [],
        ];
    }
}

function store(): JsonStore
{
    static $store;

    if ($store instanceof JsonStore) {
        return $store;
    }

    $store = new JsonStore(BASE_PATH . '/storage/data.json');
    return $store;
}
