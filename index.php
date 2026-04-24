<?php

declare(strict_types=1);

require __DIR__ . '/config/bootstrap.php';

$articles = app('articles')->latest(9);
$settings = app('settings')->all();
$allPublished = app('articles')->listPublished();
$categories = [];
foreach ($allPublished as $article) {
    $slug = $article['category_slug'];
    $categories[$slug] = [
        'name' => $article['category_name'],
        'slug' => $slug,
        'count' => ($categories[$slug]['count'] ?? 0) + 1,
    ];
}

ob_start();
?>
<section class="hero">
    <div class="wrap hero-grid">
        <div>
            <span class="eyebrow">Open-source GEO / SEO content operations</span>
            <h1><?= e($settings['site_name'] ?? 'GEOFlow') ?></h1>
            <p class="lead"><?= e($settings['site_tagline'] ?? t('tagline')) ?></p>
            <div class="hero-actions">
                <a class="button primary" href="/geo_admin/">Open Admin</a>
                <a class="button secondary" href="/api/v1/">API</a>
                <a class="button ghost" href="/archive.php">Archive</a>
            </div>
        </div>
        <div class="pipeline-card">
            <h2>Runtime Workflow</h2>
            <ol>
                <li>Configure models, prompts, and assets</li>
                <li>Create and queue generation tasks</li>
                <li>Run worker execution</li>
                <li>Review and enrich metadata</li>
                <li>Publish structured SEO pages</li>
            </ol>
            <div class="mini-stats">
                <div><strong><?= e((string) count($allPublished)) ?></strong><span>Published</span></div>
                <div><strong><?= e((string) count($categories)) ?></strong><span>Categories</span></div>
                <div><strong>5</strong><span>Languages</span></div>
            </div>
        </div>
    </div>
</section>

<section class="wrap section">
    <div class="section-head">
        <h2>What GEOFlow Does</h2>
        <p>A complete content operations pipeline for GEO and SEO work.</p>
    </div>
    <div class="card-grid">
        <article class="card reveal">
            <h3>Multi-model content generation</h3>
            <p>Connect OpenAI-style endpoints, select models per task, and fall back to the internal generator when needed.</p>
        </article>
        <article class="card reveal">
            <h3>Asset management</h3>
            <p>Maintain prompts, knowledge base entries, keywords, and reusable materials in one workspace.</p>
        </article>
        <article class="card reveal">
            <h3>Queue and scheduling</h3>
            <p>Create due tasks, queue them for execution, and process drafts through worker jobs with retry states.</p>
        </article>
        <article class="card reveal">
            <h3>Review and publish</h3>
            <p>Move articles from draft and review into published SEO pages with metadata and JSON-LD.</p>
        </article>
    </div>
</section>

<section class="wrap section">
    <div class="section-head">
        <h2>Browse Categories</h2>
        <p>Published topics currently available on the frontend.</p>
    </div>
    <div class="card-grid">
        <?php if ($categories): ?>
            <?php foreach ($categories as $category): ?>
                <article class="card">
                    <span class="pill"><?= e((string) $category['count']) ?> article<?= $category['count'] === 1 ? '' : 's' ?></span>
                    <h3><?= e($category['name']) ?></h3>
                    <a class="text-link" href="/category.php?slug=<?= e($category['slug']) ?>">Open category</a>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <article class="card">
                <h3>No published categories yet</h3>
                <p>Publish at least one article from the admin review workflow and categories will appear here.</p>
            </article>
        <?php endif; ?>
    </div>
</section>

<section class="wrap section">
    <div class="section-head">
        <h2>Latest Published Content</h2>
        <p>Preview-first article delivery with metadata and structured publishing.</p>
    </div>
    <div class="card-grid">
        <?php if ($articles): ?>
            <?php foreach ($articles as $article): ?>
                <article class="card reveal">
                    <span class="pill"><?= e($article['category_name']) ?></span>
                    <h3><a href="/article.php?slug=<?= e($article['slug']) ?>"><?= e($article['title']) ?></a></h3>
                    <p><?= e($article['summary']) ?></p>
                    <a class="text-link" href="/article.php?slug=<?= e($article['slug']) ?>">Read article</a>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <article class="card">
                <h3>No published articles yet</h3>
                <p>Open the admin panel, run the scheduler and worker, then publish the generated draft.</p>
            </article>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
$pageTitle = 'GEOFlow';
$seoTitle = 'GEOFlow | GEO and SEO Content Operations';
$seoDescription = 'Open-source GEO/SEO content production system with models, assets, queue workers, workflow review, and publishing.';
render('layout', compact('content', 'pageTitle', 'seoTitle', 'seoDescription'));
