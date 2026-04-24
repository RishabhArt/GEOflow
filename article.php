<?php

declare(strict_types=1);

require __DIR__ . '/config/bootstrap.php';

$slug = (string) request_input('slug', '');
$article = app('articles')->findBySlug($slug);

if (!$article) {
    http_response_code(404);
    exit('Article not found.');
}

$structured = $article['structured_data_json']
    ? json_encode($article['structured_data_json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    : null;

ob_start();
?>
<article class="wrap article-shell">
    <div class="article-meta">
        <span class="pill"><?= e($article['category_name']) ?></span>
        <span><?= e((string) $article['published_at']) ?></span>
        <span><?= e($article['source_model']) ?></span>
    </div>
    <h1><?= e($article['title']) ?></h1>
    <p class="lead"><?= e($article['summary']) ?></p>
    <div class="article-body"><?= $article['body_html'] ?></div>
</article>
<?php
$content = ob_get_clean();
$pageTitle = $article['title'];
$seoTitle = $article['seo_title'];
$seoDescription = $article['seo_description'];
$structuredDataJson = $structured;
render('layout', compact('content', 'pageTitle', 'seoTitle', 'seoDescription', 'structuredDataJson'));
