<?php

declare(strict_types=1);

function seed_demo_workspace(): void
{
    $assetsService = app('assets');
    $tasksService = app('tasks');
    $articlesService = app('articles');

    $assets = $assetsService->listAll();
    if (count($assets) < 3) {
        $existingTitles = array_map(fn (array $asset): string => $asset['title'], $assets);

        $demoAssets = [
            [
                'asset_type' => 'knowledge',
                'title' => 'Trust-building knowledge base',
                'content' => 'Use verifiable claims, practical examples, and audience-specific structure.',
            ],
            [
                'asset_type' => 'prompt',
                'title' => 'Editorial prompt',
                'content' => 'Write with clear sections, concise paragraphs, and production-ready SEO fields.',
            ],
            [
                'asset_type' => 'keyword',
                'title' => 'GEO workflow automation',
                'content' => 'GEO workflow automation, AI content ops, SEO publishing pipeline',
            ],
        ];

        foreach ($demoAssets as $asset) {
            if (!in_array($asset['title'], $existingTitles, true)) {
                $assetsService->create($asset + ['meta' => ['seed' => true]]);
            }
        }
    }

    $tasks = $tasksService->listAll();
    if ($tasks === []) {
        $seedAssets = array_map(fn (array $asset): int => (int) $asset['id'], array_slice($assetsService->listAll(), 0, 2));
        $tasksService->create([
            'name' => 'Platform overview article',
            'model_id' => '',
            'asset_ids' => $seedAssets,
            'title_seed' => 'What a GEO content operations system needs to scale',
            'primary_keyword' => 'GEO content operations system',
            'language_code' => 'en',
            'audience' => 'Content operations teams',
            'category_name' => 'Platform',
            'prompt_template' => 'Write a strategic overview with implementation steps.',
            'publish_mode' => 'review',
            'schedule_at' => now_utc(),
        ]);
    }

    $allArticles = $articlesService->listAll();
    $published = $articlesService->listPublished();

    if ($published === []) {
        foreach ($allArticles as $article) {
            if (in_array($article['status'], ['review', 'draft'], true)) {
                $articlesService->transition((int) $article['id'], 'published');
                $published = $articlesService->listPublished();
                break;
            }
        }
    }

    if (count($articlesService->listPublished()) < 2) {
        $demoArticles = [
            [
                'task_id' => null,
                'title' => 'How GEOFlow turns content ops into a repeatable pipeline',
                'slug' => 'how-geoflow-turns-content-ops-into-a-repeatable-pipeline',
                'category_name' => 'Platform',
                'summary' => 'A practical walkthrough of task creation, queueing, review, and publishing in GEOFlow.',
                'body_html' => '<p>GEOFlow is built to connect prompts, assets, models, queue execution, and editorial review into one operating system for content teams.</p><h2>Core loop</h2><p>Teams define a title seed, keywords, audience, and supporting knowledge. The scheduler queues work, the worker generates drafts, and editors move the content through review to publication.</p><h2>Why this matters</h2><p>Without a repeatable workflow, content operations become fragmented. GEOFlow standardizes creation, metadata, and publishing so the system remains usable as output volume grows.</p>',
                'status' => 'published',
                'seo_title' => 'How GEOFlow turns content ops into a repeatable pipeline',
                'seo_description' => 'Repeatable GEO and SEO content operations with queueing, review, and publishing.',
                'open_graph_title' => 'How GEOFlow turns content ops into a repeatable pipeline',
                'open_graph_description' => 'Repeatable GEO and SEO content operations with queueing, review, and publishing.',
                'structured_data' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => 'How GEOFlow turns content ops into a repeatable pipeline',
                ],
                'source_model' => 'demo-publisher',
            ],
            [
                'task_id' => null,
                'title' => 'Designing a GEO knowledge base before scaling AI generation',
                'slug' => 'designing-a-geo-knowledge-base-before-scaling-ai-generation',
                'category_name' => 'Strategy',
                'summary' => 'Why a reliable knowledge base should come before heavy automation in GEO-driven publishing.',
                'body_html' => '<p>Strong GEO systems start with useful source material. A knowledge base gives AI workflows boundaries, proof points, and a stable editorial point of view.</p><h2>What to store</h2><p>Store product truths, audience pain points, source links, differentiators, and reusable prompt components.</p><h2>Operational result</h2><p>When the worker generates drafts from good assets, review becomes faster and published pages become more consistent and trustworthy.</p>',
                'status' => 'published',
                'seo_title' => 'Designing a GEO knowledge base before scaling AI generation',
                'seo_description' => 'Build trust-first knowledge assets before scaling automated GEO content generation.',
                'open_graph_title' => 'Designing a GEO knowledge base before scaling AI generation',
                'open_graph_description' => 'Build trust-first knowledge assets before scaling automated GEO content generation.',
                'structured_data' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => 'Designing a GEO knowledge base before scaling AI generation',
                ],
                'source_model' => 'demo-publisher',
            ],
        ];

        $existingSlugs = array_map(fn (array $article): string => $article['slug'], $articlesService->listAll());
        foreach ($demoArticles as $article) {
            if (!in_array($article['slug'], $existingSlugs, true)) {
                $articlesService->createGeneratedArticle($article);
            }
        }
    }
}
