<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

$assetId = app('assets')->create([
    'asset_type' => 'knowledge',
    'title' => 'GEO starter knowledge base',
    'content' => 'Focus on helpful, verifiable, audience-driven content that improves discovery and trust.',
    'meta' => ['seed' => true],
]);

$taskId = app('tasks')->create([
    'name' => 'Demo GEO article',
    'model_id' => '',
    'asset_ids' => [$assetId],
    'title_seed' => 'How GEOFlow structures GEO content operations',
    'primary_keyword' => 'GEO content operations',
    'language_code' => 'en',
    'audience' => 'Marketing and content operations teams',
    'category_name' => 'Platform',
    'prompt_template' => 'Write a practical overview for teams adopting GEO workflows.',
    'publish_mode' => 'review',
    'schedule_at' => now_utc(),
]);

cli_log('Demo asset #' . $assetId . ' and task #' . $taskId . ' created.');

