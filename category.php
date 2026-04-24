<?php

declare(strict_types=1);

require __DIR__ . '/config/bootstrap.php';

$slug = (string) request_input('slug', 'insights');
$articles = app('articles')->listPublished($slug);

ob_start();
?>
<section class="wrap section">
    <div class="section-head">
        <h1>Category: <?= e(ucwords(str_replace('-', ' ', $slug))) ?></h1>
        <p>Published content filtered by category.</p>
    </div>
    <div class="card-grid">
        <?php foreach ($articles as $article): ?>
            <article class="card">
                <h3><a href="/article.php?slug=<?= e($article['slug']) ?>"><?= e($article['title']) ?></a></h3>
                <p><?= e($article['summary']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
$pageTitle = 'Category';
render('layout', compact('content', 'pageTitle'));

