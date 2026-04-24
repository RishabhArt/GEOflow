<?php
$settings = app('settings')->all();
$theme = $settings['theme'] ?? 'aurora';
$siteName = $settings['site_name'] ?? 'GEOFlow';
$siteDescription = $settings['site_description'] ?? 'Open-source GEO content production platform.';
$seoTitle = $seoTitle ?? $pageTitle ?? $siteName;
$seoDescription = $seoDescription ?? $siteDescription;
$canonical = $canonical ?? base_url(ltrim(request_path(), '/'));
$locale = active_locale();
$bodyClass = $bodyClass ?? '';
$themeVars = [
    'aurora' => ['#09111f', '#153f59', '#f4efe7', '#6af0c7'],
    'ember' => ['#1b120e', '#6d2f1f', '#fff3e8', '#ff9857'],
    'sage' => ['#0c1714', '#28453a', '#eef4ec', '#97d27a'],
];
$palette = $themeVars[$theme] ?? $themeVars['aurora'];
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($seoTitle) ?></title>
    <meta name="description" content="<?= e($seoDescription) ?>">
    <meta property="og:title" content="<?= e($seoTitle) ?>">
    <meta property="og:description" content="<?= e($seoDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <link rel="stylesheet" href="/public_assets/css/app.css">
    <style>
        :root {
            --bg: <?= e($palette[0]) ?>;
            --panel: <?= e($palette[1]) ?>;
            --text: <?= e($palette[2]) ?>;
            --accent: <?= e($palette[3]) ?>;
        }
    </style>
    <?php if (!empty($structuredDataJson)): ?>
        <script type="application/ld+json"><?= $structuredDataJson ?></script>
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<header class="site-header">
    <div class="wrap nav-shell">
        <a class="brand" href="/"><?= e($siteName) ?></a>
        <nav class="nav-links">
            <a href="/"><?= e(t('articles')) ?></a>
            <a href="/archive.php">Archive</a>
            <a href="/geo_admin/"> <?= e(t('admin')) ?></a>
        </nav>
        <div class="lang-switcher">
            <?php foreach (language_options() as $code => $label): ?>
                <a href="<?= e(request_path()) ?>?lang=<?= e($code) ?>" class="<?= $locale === $code ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</header>
<main>
    <?= $content ?>
</main>
<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <h3><?= e($siteName) ?></h3>
            <p><?= e($settings['site_tagline'] ?? t('tagline')) ?></p>
        </div>
        <div>
            <p>Pipeline: Admin → Scheduler → Worker → Draft → Review → Publish</p>
            <p>Recommended runtime: Docker Compose + PostgreSQL</p>
        </div>
    </div>
</footer>
<script src="/public_assets/js/app.js"></script>
</body>
</html>

