<?php

declare(strict_types=1);

class TaskService
{
    public function __construct(private JsonStore $store)
    {
    }

    public function listAll(): array
    {
        $rows = $this->store->all('tasks');
        usort($rows, fn (array $a, array $b): int => $b['id'] <=> $a['id']);
        return $rows;
    }

    public function create(array $data): int
    {
        $scheduleAt = (string) ($data['schedule_at'] ?: now_utc());
        $scheduleAt = str_replace('T', ' ', $scheduleAt);
        if (strlen($scheduleAt) === 16) {
            $scheduleAt .= ':00';
        }

        return $this->store->insert('tasks', [
            'name' => $data['name'],
            'model_id' => $data['model_id'] !== '' ? (int) $data['model_id'] : null,
            'asset_ids_json' => array_values($data['asset_ids'] ?? []),
            'title_seed' => $data['title_seed'],
            'primary_keyword' => $data['primary_keyword'],
            'language_code' => $data['language_code'],
            'audience' => $data['audience'],
            'category_name' => $data['category_name'],
            'prompt_template' => $data['prompt_template'],
            'publish_mode' => $data['publish_mode'],
            'schedule_at' => $scheduleAt,
            'status' => 'pending',
            'article_id' => null,
            'last_error' => null,
            'created_at' => now_utc(),
            'updated_at' => now_utc(),
        ]);
    }

    public function find(int $id): ?array
    {
        return $this->store->find('tasks', $id);
    }

    public function dueTasks(): array
    {
        $rows = $this->store->where('tasks', fn (array $row): bool => $row['status'] === 'pending' && $row['schedule_at'] <= now_utc());
        usort($rows, fn (array $a, array $b): int => $a['id'] <=> $b['id']);
        return $rows;
    }

    public function markQueued(int $id): void
    {
        $this->store->update($id, 'tasks', [
            'status' => 'queued',
            'updated_at' => now_utc(),
        ]);
    }

    public function markProcessing(int $id): void
    {
        $this->store->update($id, 'tasks', [
            'status' => 'processing',
            'updated_at' => now_utc(),
        ]);
    }

    public function markComplete(int $id, int $articleId): void
    {
        $this->store->update($id, 'tasks', [
            'status' => 'completed',
            'article_id' => $articleId,
            'updated_at' => now_utc(),
        ]);
    }

    public function markFailed(int $id, string $message): void
    {
        $this->store->update($id, 'tasks', [
            'status' => 'failed',
            'last_error' => mb_substr($message, 0, 1000),
            'updated_at' => now_utc(),
        ]);
    }

    public function countsByStatus(): array
    {
        $counts = [];
        foreach ($this->store->all('tasks') as $task) {
            $status = $task['status'];
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }
        ksort($counts);
        return $counts;
    }

    public function duplicate(int $id): ?int
    {
        $task = $this->find($id);
        if (!$task) {
            return null;
        }

        unset($task['id']);
        $task['name'] .= ' (Copy)';
        $task['status'] = 'pending';
        $task['article_id'] = null;
        $task['last_error'] = null;
        $task['schedule_at'] = now_utc();
        $task['created_at'] = now_utc();
        $task['updated_at'] = now_utc();

        return $this->store->insert('tasks', $task);
    }
}
