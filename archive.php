<?php

declare(strict_types=1);

require __DIR__ . '/config/bootstrap.php';

$groups = app('articles')->archiveGroups();

ob_start();
?>
<section class="wrap section">
    <div class="section-head">
        <h1>Archive</h1>
        <p>Monthly roll-up of published GEOFlow content.</p>
    </div>
    <div class="card-grid">
        <?php foreach ($groups as $group): ?>
            <article class="card">
                <h3><?= e($group['period']) ?></h3>
                <p><?= e((string) $group['total']) ?> published article(s)</p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
$pageTitle = 'Archive';
render('layout', compact('content', 'pageTitle'));

