<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

$once = in_array('--once', $argv ?? [], true);

$run = function (): bool {
    $job = app('queue')->claimNext();
    if (!$job) {
        cli_log('No queued jobs available.');
        return false;
    }

    $taskId = (int) $job['task_id'];

    try {
        $task = app('tasks')->find($taskId);
        if (!$task) {
            throw new RuntimeException('Task not found.');
        }

        app('tasks')->markProcessing($taskId);
        $assetIds = is_array($task['asset_ids_json'] ?? null) ? $task['asset_ids_json'] : [];
        $assets = app('assets')->findMany(array_map('intval', $assetIds));
        $model = !empty($task['model_id']) ? app('ai')->findModel((int) $task['model_id']) : null;
        $generated = app('ai')->generateArticle($task, $assets, $model);

        $publishMode = $task['publish_mode'] === 'auto' || (app('settings')->get('auto_publish', 'false') === 'true');
        $status = $publishMode ? 'published' : 'review';
        $structured = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $generated['title'],
            'description' => $generated['seo_description'],
            'datePublished' => date(DATE_ATOM),
        ];

        $articleId = app('articles')->createGeneratedArticle([
            'task_id' => $taskId,
            'title' => $generated['title'],
            'slug' => slugify($generated['title'] . '-' . $taskId),
            'category_name' => $generated['category_name'],
            'summary' => $generated['summary'],
            'body_html' => $generated['body_html'],
            'status' => $status,
            'seo_title' => $generated['seo_title'],
            'seo_description' => $generated['seo_description'],
            'open_graph_title' => $generated['seo_title'],
            'open_graph_description' => $generated['seo_description'],
            'structured_data' => $structured,
            'source_model' => $generated['source_model'],
        ]);

        app('tasks')->markComplete($taskId, $articleId);
        app('queue')->complete((int) $job['id']);
        cli_log('Processed task #' . $taskId . ' into article #' . $articleId);
        return true;
    } catch (Throwable $exception) {
        app('tasks')->markFailed($taskId, $exception->getMessage());
        app('queue')->fail((int) $job['id'], $exception->getMessage(), (int) $job['attempts']);
        cli_log('Job #' . $job['id'] . ' failed: ' . $exception->getMessage());
        return true;
    }
};

if ($once) {
    $run();
    exit;
}

$interval = (int) config('CRON_INTERVAL', 60);
while (true) {
    $processed = $run();
    sleep($processed ? 1 : max(5, $interval));
}
