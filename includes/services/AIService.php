<?php

declare(strict_types=1);

class AIService
{
    public function __construct(private JsonStore $store)
    {
    }

    public function listModels(): array
    {
        $rows = $this->store->all('ai_models');
        usort($rows, fn (array $a, array $b): int => $b['id'] <=> $a['id']);
        return $rows;
    }

    public function createModel(array $data): int
    {
        return $this->store->insert('ai_models', [
            'name' => $data['name'],
            'provider_name' => $data['provider_name'],
            'api_url' => rtrim($data['api_url'], '/'),
            'model_id' => $data['model_id'],
            'api_key' => $data['api_key'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_at' => now_utc(),
            'updated_at' => now_utc(),
        ]);
    }

    public function findModel(int $id): ?array
    {
        return $this->store->find('ai_models', $id);
    }

    public function generateArticle(array $task, array $assets, ?array $model): array
    {
        if ($model && function_exists('curl_init') && !empty($model['api_url']) && !empty($model['api_key'])) {
            $remote = $this->tryRemoteGeneration($task, $assets, $model);
            if ($remote !== null) {
                return $remote;
            }
        }

        return $this->fallbackGeneration($task, $assets, $model);
    }

    private function tryRemoteGeneration(array $task, array $assets, array $model): ?array
    {
        $prompt = $this->buildPrompt($task, $assets);
        $endpoint = $model['api_url'] . '/chat/completions';
        $payload = [
            'model' => $model['model_id'],
            'messages' => [
                ['role' => 'system', 'content' => 'You are a GEO and SEO content strategist. Return JSON only.'],
                ['role' => 'user', 'content' => $prompt . "\n\nReturn JSON with keys: title, summary, category_name, body_html, seo_title, seo_description."],
            ],
            'temperature' => 0.7,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $model['api_key'],
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            return null;
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content)) {
            return null;
        }

        $json = json_decode(trim($content), true);
        if (!is_array($json)) {
            return null;
        }

        return [
            'title' => $json['title'] ?? $task['title_seed'],
            'summary' => $json['summary'] ?? '',
            'category_name' => $json['category_name'] ?? ($task['category_name'] ?? 'Insights'),
            'body_html' => $json['body_html'] ?? '<p>No content returned.</p>',
            'seo_title' => $json['seo_title'] ?? ($json['title'] ?? $task['title_seed']),
            'seo_description' => $json['seo_description'] ?? ($json['summary'] ?? ''),
            'source_model' => $model['name'],
        ];
    }

    private function buildPrompt(array $task, array $assets): string
    {
        $sections = [];
        $sections[] = 'Title seed: ' . ($task['title_seed'] ?? 'Untitled');
        $sections[] = 'Primary keyword: ' . ($task['primary_keyword'] ?? '');
        $sections[] = 'Language: ' . ($task['language_code'] ?? 'en');
        $sections[] = 'Audience: ' . ($task['audience'] ?? 'General professional audience');
        $sections[] = 'Prompt notes: ' . ($task['prompt_template'] ?? '');

        foreach ($assets as $asset) {
            $sections[] = strtoupper($asset['asset_type']) . ': ' . $asset['title'] . ' => ' . $asset['content'];
        }

        return implode("\n", $sections);
    }

    private function fallbackGeneration(array $task, array $assets, ?array $model): array
    {
        $title = $task['title_seed'] ?: 'Untitled GEO article';
        $keyword = $task['primary_keyword'] ?: 'generative engine optimization';
        $category = $task['category_name'] ?: 'Insights';
        $language = $task['language_code'] ?: 'en';

        $assetLines = [];
        foreach ($assets as $asset) {
            $assetLines[] = '<li><strong>' . e($asset['title']) . ':</strong> ' . e($asset['content']) . '</li>';
        }

        $bodyHtml = implode('', [
            '<p>' . e($title) . ' is generated through GEOFlow\'s automated pipeline with emphasis on discoverability, structured publishing, and reusable knowledge assets.</p>',
            '<h2>Why this topic matters</h2>',
            '<p>The target keyword <strong>' . e($keyword) . '</strong> is paired with a production workflow that supports scheduling, review, and publishing without losing editorial control.</p>',
            '<h2>Operational framework</h2>',
            '<ul>',
            '<li>Define an intent-driven title and target audience.</li>',
            '<li>Attach prompts, knowledge base notes, and reusable assets.</li>',
            '<li>Run generation through queue workers and review before publishing.</li>',
            '</ul>',
            '<h2>Supporting assets</h2>',
            $assetLines ? '<ul>' . implode('', $assetLines) . '</ul>' : '<p>No extra assets were attached to this task, so GEOFlow used the built-in fallback writer.</p>',
            '<h2>SEO execution</h2>',
            '<p>This draft includes metadata scaffolding, Open Graph fields, and schema-ready structured data so teams can move from draft to publication quickly.</p>',
            '<p><em>Language:</em> ' . e($language) . '</p>',
        ]);

        return [
            'title' => $title,
            'summary' => 'Automated GEO article for ' . $keyword . ' with review-ready structure.',
            'category_name' => $category,
            'body_html' => $bodyHtml,
            'seo_title' => $title . ' | GEOFlow',
            'seo_description' => 'Review-ready GEO content focused on ' . $keyword . '.',
            'source_model' => $model['name'] ?? 'fallback-writer',
        ];
    }
}
