<?php

declare(strict_types=1);

class QueueService
{
    public function __construct(private JsonStore $store)
    {
    }

    public function enqueue(string $jobType, array $payload, int $taskId): int
    {
        return $this->store->insert('jobs', [
            'task_id' => $taskId,
            'job_type' => $jobType,
            'payload_json' => $payload,
            'status' => 'queued',
            'attempts' => 0,
            'error_message' => null,
            'available_at' => now_utc(),
            'created_at' => now_utc(),
            'updated_at' => now_utc(),
        ]);
    }

    public function listRecent(): array
    {
        $rows = $this->store->all('jobs');
        usort($rows, fn (array $a, array $b): int => $b['id'] <=> $a['id']);
        return array_slice($rows, 0, 20);
    }

    public function claimNext(): ?array
    {
        $jobs = $this->store->where(
            'jobs',
            fn (array $job): bool => in_array($job['status'], ['queued', 'retry'], true) && $job['available_at'] <= now_utc()
        );
        usort($jobs, fn (array $a, array $b): int => $a['id'] <=> $b['id']);
        $job = $jobs[0] ?? null;

        if (!$job) {
            return null;
        }

        $attempts = (int) $job['attempts'] + 1;
        $this->store->update((int) $job['id'], 'jobs', [
            'status' => 'processing',
            'attempts' => $attempts,
            'updated_at' => now_utc(),
        ]);
        $job['attempts'] = $attempts;

        return $job;
    }

    public function complete(int $jobId): void
    {
        $this->store->update($jobId, 'jobs', [
            'status' => 'done',
            'updated_at' => now_utc(),
        ]);
    }

    public function fail(int $jobId, string $message, int $attempts): void
    {
        $retryLimit = (int) config('QUEUE_RETRY_LIMIT', 3);
        $status = $attempts >= $retryLimit ? 'failed' : 'retry';
        $availableAt = $status === 'retry' ? gmdate('Y-m-d H:i:s', time() + 60) : now_utc();

        $this->store->update($jobId, 'jobs', [
            'status' => $status,
            'error_message' => mb_substr($message, 0, 1000),
            'available_at' => $availableAt,
            'updated_at' => now_utc(),
        ]);
    }

    public function countsByStatus(): array
    {
        $counts = [];
        foreach ($this->store->all('jobs') as $job) {
            $status = $job['status'];
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }
        ksort($counts);
        return $counts;
    }
}
