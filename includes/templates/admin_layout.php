<?php
$title = $title ?? 'GEOFlow Admin';
$flashSuccess = flash('success');
$flashError = flash('error');
$showSidebar = ($showSidebar ?? true) && current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/public_assets/css/app.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <?php if ($showSidebar): ?>
        <aside class="admin-sidebar">
            <a class="brand" href="/geo_admin/">GEOFlow</a>
            <a href="/geo_admin/?tab=dashboard">Dashboard</a>
            <a href="/geo_admin/?tab=models">AI Models</a>
            <a href="/geo_admin/?tab=assets">Assets</a>
            <a href="/geo_admin/?tab=tasks">Tasks</a>
            <a href="/geo_admin/?tab=articles">Articles</a>
            <a href="/geo_admin/?tab=settings">Settings</a>
            <a href="/geo_admin/logout.php">Logout</a>
        </aside>
    <?php endif; ?>
    <section class="admin-main">
        <?php if ($flashSuccess): ?><div class="flash success"><?= e($flashSuccess) ?></div><?php endif; ?>
        <?php if ($flashError): ?><div class="flash error"><?= e($flashError) ?></div><?php endif; ?>
        <?= $content ?>
    </section>
</div>
</body>
</html>
