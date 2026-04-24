<?php

declare(strict_types=1);

class ArticleService
{
    public function __construct(private JsonStore $store)
    {
    }

    public function listPublished(?string $category = null): array
    {
        $rows = $this->store->where('articles', function (array $row) use ($category): bool {
            return $row['status'] === 'published' && ($category === null || $row['category_slug'] === $category);
        });

        usort($rows, fn (array $a, array $b): int => strcmp((string) ($b['published_at'] ?? ''), (string) ($a['published_at'] ?? '')) ?: ($b['id'] <=> $a['id']));
        return $rows;
    }

    public function listAll(): array
    {
        $rows = $this->store->all('articles');
        usort($rows, fn (array $a, array $b): int => $b['id'] <=> $a['id']);
        return $rows;
    }

    public function latest(int $limit = 6): array
    {
        return array_slice($this->listPublished(), 0, $limit);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->store->first('articles', fn (array $row): bool => $row['slug'] === $slug);
    }

    public function createGeneratedArticle(array $payload): int
    {
        $status = $payload['status'] ?? 'draft';

        return $this->store->insert('articles', [
            'task_id' => $payload['task_id'],
            'title' => $payload['title'],
            'slug' => $payload['slug'] ?: slugify($payload['title']),
            'category_name' => $payload['category_name'] ?? 'Insights',
            'category_slug' => slugify($payload['category_name'] ?? 'Insights'),
            'summary' => $payload['summary'] ?? '',
            'body_html' => $payload['body_html'] ?? '',
            'status' => $status,
            'seo_title' => $payload['seo_title'] ?? $payload['title'],
            'seo_description' => $payload['seo_description'] ?? $payload['summary'] ?? '',
            'open_graph_title' => $payload['open_graph_title'] ?? $payload['title'],
            'open_graph_description' => $payload['open_graph_description'] ?? $payload['summary'] ?? '',
            'structured_data_json' => $payload['structured_data'] ?? [],
            'source_model' => $payload['source_model'] ?? 'fallback-writer',
            'created_at' => now_utc(),
            'updated_at' => now_utc(),
            'published_at' => $status === 'published' ? now_utc() : null,
        ]);
    }

    public function transition(int $id, string $status): void
    {
        if (!in_array($status, ['draft', 'review', 'published', 'archived'], true)) {
            throw new InvalidArgumentException('Invalid article status.');
        }

        $this->store->update($id, 'articles', [
            'status' => $status,
            'updated_at' => now_utc(),
            'published_at' => $status === 'published' ? now_utc() : null,
        ]);
    }

    public function archiveGroups(): array
    {
        $groups = [];
        foreach ($this->listPublished() as $article) {
            $period = substr((string) ($article['published_at'] ?? $article['created_at']), 0, 7);
            $groups[$period] = ($groups[$period] ?? 0) + 1;
        }

        krsort($groups);
        $rows = [];
        foreach ($groups as $period => $total) {
            $rows[] = ['period' => $period, 'total' => $total];
        }

        return $rows;
    }

    public function countsByStatus(): array
    {
        $counts = [
            'draft' => 0,
            'review' => 0,
            'published' => 0,
            'archived' => 0,
        ];

        foreach ($this->store->all('articles') as $article) {
            $status = $article['status'] ?? 'draft';
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    public function recentReviewQueue(int $limit = 5): array
    {
        $rows = $this->store->where('articles', fn (array $row): bool => in_array($row['status'], ['draft', 'review'], true));
        usort($rows, fn (array $a, array $b): int => strcmp((string) $b['updated_at'], (string) $a['updated_at']) ?: ($b['id'] <=> $a['id']));
        return array_slice($rows, 0, $limit);
    }

    public function updateContent(int $id, array $payload): void
    {
        $this->store->update($id, 'articles', [
            'title' => $payload['title'],
            'summary' => $payload['summary'],
            'body_html' => $payload['body_html'],
            'seo_title' => $payload['seo_title'],
            'seo_description' => $payload['seo_description'],
            'open_graph_title' => $payload['open_graph_title'],
            'open_graph_description' => $payload['open_graph_description'],
            'updated_at' => now_utc(),
        ]);
    }
}
