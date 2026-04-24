<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

if (is_post()) {
    verify_csrf();

    if (request_input('action') === 'login') {
        $ok = attempt_login(store(), (string) request_input('username', ''), (string) request_input('password', ''));
        if ($ok) {
            flash('success', 'Login successful.');
            redirect('/geo_admin/');
        }

        flash('error', 'Invalid login credentials.');
        redirect('/geo_admin/?login=1');
    }
}

if (request_input('login') || !current_user()) {
    ob_start();
    ?>
    <div class="auth-card">
        <h1>GEOFlow Admin</h1>
        <p>Sign in to manage models, assets, queue execution, review, and publishing.</p>
        <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">
            <label>Username <input type="text" name="username" value="admin" required></label>
            <label>Password <input type="password" name="password" value="admin888" required></label>
            <button class="button primary" type="submit">Sign in</button>
        </form>
    </div>
    <?php
    $content = ob_get_clean();
    $title = 'Admin Login';
    $showSidebar = false;
    require BASE_PATH . '/includes/templates/admin_layout.php';
    exit;
}

require_admin();

$tab = (string) request_input('tab', 'dashboard');
$settingsService = app('settings');
$assetsService = app('assets');
$articlesService = app('articles');
$queueService = app('queue');
$aiService = app('ai');
$taskService = app('tasks');

if (is_post()) {
    verify_csrf();
    $action = (string) request_input('action', '');

    try {
        if ($action === 'create_model') {
            $aiService->createModel([
                'name' => (string) request_input('name', ''),
                'provider_name' => (string) request_input('provider_name', ''),
                'api_url' => (string) request_input('api_url', ''),
                'model_id' => (string) request_input('model_id', ''),
                'api_key' => (string) request_input('api_key', ''),
                'is_active' => (string) request_input('is_active', '') === '1',
            ]);
            flash('success', 'AI model added.');
            redirect('/geo_admin/?tab=models');
        }

        if ($action === 'create_asset') {
            $assetsService->create([
                'asset_type' => (string) request_input('asset_type', 'prompt'),
                'title' => (string) request_input('title', ''),
                'content' => (string) request_input('content', ''),
                'meta' => ['language' => (string) request_input('language', 'en')],
            ]);
            flash('success', 'Asset created.');
            redirect('/geo_admin/?tab=assets');
        }

        if ($action === 'create_task') {
            $taskService->create([
                'name' => (string) request_input('name', ''),
                'model_id' => (string) request_input('model_id', ''),
                'asset_ids' => array_map('intval', (array) ($_POST['asset_ids'] ?? [])),
                'title_seed' => (string) request_input('title_seed', ''),
                'primary_keyword' => (string) request_input('primary_keyword', ''),
                'language_code' => (string) request_input('language_code', 'en'),
                'audience' => (string) request_input('audience', 'General audience'),
                'category_name' => (string) request_input('category_name', 'Insights'),
                'prompt_template' => (string) request_input('prompt_template', ''),
                'publish_mode' => (string) request_input('publish_mode', 'review'),
                'schedule_at' => (string) request_input('schedule_at', ''),
            ]);
            flash('success', 'Task created.');
            redirect('/geo_admin/?tab=tasks');
        }

        if ($action === 'duplicate_task') {
            $newId = $taskService->duplicate((int) request_input('task_id', 0));
            flash('success', $newId ? 'Task duplicated.' : 'Task not found.');
            redirect('/geo_admin/?tab=tasks');
        }

        if ($action === 'seed_demo') {
            seed_demo_workspace();
            flash('success', 'Demo assets and task are ready.');
            redirect('/geo_admin/?tab=dashboard');
        }

        if ($action === 'run_scheduler') {
            $dueTasks = $taskService->dueTasks();
            foreach ($dueTasks as $task) {
                $queueService->enqueue('generate_article', ['task_id' => $task['id']], (int) $task['id']);
                $taskService->markQueued((int) $task['id']);
            }
            flash('success', $dueTasks ? count($dueTasks) . ' task(s) queued.' : 'No due tasks to queue.');
            redirect('/geo_admin/?tab=dashboard');
        }

        if ($action === 'run_worker_once') {
            $job = $queueService->claimNext();
            if (!$job) {
                flash('success', 'No queued jobs available.');
                redirect('/geo_admin/?tab=dashboard');
            }

            $taskId = (int) $job['task_id'];
            $task = $taskService->find($taskId);
            if (!$task) {
                throw new RuntimeException('Task not found.');
            }

            $taskService->markProcessing($taskId);
            $assetIds = is_array($task['asset_ids_json'] ?? null) ? $task['asset_ids_json'] : [];
            $assets = $assetsService->findMany(array_map('intval', $assetIds));
            $model = !empty($task['model_id']) ? $aiService->findModel((int) $task['model_id']) : null;
            $generated = $aiService->generateArticle($task, $assets, $model);
            $publishMode = $task['publish_mode'] === 'auto' || ($settingsService->get('auto_publish', 'false') === 'true');
            $status = $publishMode ? 'published' : 'review';

            $articleId = $articlesService->createGeneratedArticle([
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
                'structured_data' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $generated['title'],
                    'description' => $generated['seo_description'],
                    'datePublished' => date(DATE_ATOM),
                ],
                'source_model' => $generated['source_model'],
            ]);

            $taskService->markComplete($taskId, $articleId);
            $queueService->complete((int) $job['id']);
            flash('success', 'Worker processed task #' . $taskId . ' into article #' . $articleId . '.');
            redirect('/geo_admin/?tab=articles');
        }

        if ($action === 'article_transition') {
            $articlesService->transition((int) request_input('article_id', 0), (string) request_input('status', 'draft'));
            flash('success', 'Article status updated.');
            redirect('/geo_admin/?tab=articles');
        }

        if ($action === 'save_article') {
            $articleId = (int) request_input('article_id', 0);
            $articlesService->updateContent($articleId, [
                'title' => (string) request_input('title', ''),
                'summary' => (string) request_input('summary', ''),
                'body_html' => (string) request_input('body_html', ''),
                'seo_title' => (string) request_input('seo_title', ''),
                'seo_description' => (string) request_input('seo_description', ''),
                'open_graph_title' => (string) request_input('open_graph_title', ''),
                'open_graph_description' => (string) request_input('open_graph_description', ''),
            ]);
            flash('success', 'Article content saved.');
            redirect('/geo_admin/?tab=articles&edit=' . $articleId);
        }

        if ($action === 'save_settings') {
            $settingsService->setMany([
                'site_name' => (string) request_input('site_name', 'GEOFlow'),
                'site_tagline' => (string) request_input('site_tagline', ''),
                'site_description' => (string) request_input('site_description', ''),
                'theme' => (string) request_input('theme', 'aurora'),
                'auto_publish' => (string) request_input('auto_publish', 'false'),
            ]);
            flash('success', 'Settings saved.');
            redirect('/geo_admin/?tab=settings');
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
        redirect('/geo_admin/?tab=' . urlencode($tab));
    }
}

seed_demo_workspace();

$models = $aiService->listModels();
$assets = $assetsService->listAll();
$tasks = $taskService->listAll();
$articles = $articlesService->listAll();
$jobs = $queueService->listRecent();
$settings = $settingsService->all();
$taskCounts = $taskService->countsByStatus();
$articleCounts = $articlesService->countsByStatus();
$queueCounts = $queueService->countsByStatus();
$assetCounts = $assetsService->statsByType();
$reviewQueue = $articlesService->recentReviewQueue();
$editingArticle = request_input('edit') ? null : null;
if (request_input('edit')) {
    foreach ($articles as $candidate) {
        if ((int) $candidate['id'] === (int) request_input('edit')) {
            $editingArticle = $candidate;
            break;
        }
    }
}

$articleFilter = (string) request_input('status', '');
$filteredArticles = $articleFilter !== ''
    ? array_values(array_filter($articles, fn (array $article): bool => $article['status'] === $articleFilter))
    : $articles;

$publishedArticles = array_values(array_filter($articles, fn (array $article): bool => $article['status'] === 'published'));

ob_start();
?>
<div class="admin-head">
    <div>
        <h1><?= e(ucfirst($tab)) ?></h1>
        <p>Manage the GEOFlow pipeline from models to assets, generation, review, and publishing.</p>
    </div>
    <div class="stats-row">
        <div class="stat-card"><strong><?= count($tasks) ?></strong><span>Tasks</span></div>
        <div class="stat-card"><strong><?= count($articles) ?></strong><span>Articles</span></div>
        <div class="stat-card"><strong><?= count($models) ?></strong><span>Models</span></div>
    </div>
</div>

<?php if ($tab === 'dashboard'): ?>
    <section class="panel quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-row">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="seed_demo">
                <button class="button secondary" type="submit">Seed Demo Data</button>
            </form>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="run_scheduler">
                <button class="button secondary" type="submit">Run Scheduler</button>
            </form>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="run_worker_once">
                <button class="button primary" type="submit">Run Worker Once</button>
            </form>
            <a class="button ghost" href="/api/v1/" target="_blank">Open API</a>
        </div>
    </section>

    <div class="card-grid admin-grid">
        <section class="panel">
            <h2>Pipeline Snapshot</h2>
            <div class="metric-grid">
                <?php foreach ($taskCounts as $status => $count): ?>
                    <div class="metric-chip"><strong><?= e((string) $count) ?></strong><span>Task <?= e($status) ?></span></div>
                <?php endforeach; ?>
                <?php foreach ($articleCounts as $status => $count): ?>
                    <div class="metric-chip"><strong><?= e((string) $count) ?></strong><span>Article <?= e($status) ?></span></div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="panel">
            <h2>Asset Mix</h2>
            <div class="metric-grid">
                <?php foreach ($assetCounts as $type => $count): ?>
                    <div class="metric-chip"><strong><?= e((string) $count) ?></strong><span><?= e(ucfirst($type)) ?></span></div>
                <?php endforeach; ?>
            </div>
            <h3>Queue Status</h3>
            <div class="metric-grid">
                <?php foreach ($queueCounts as $status => $count): ?>
                    <div class="metric-chip"><strong><?= e((string) $count) ?></strong><span><?= e(ucfirst($status)) ?></span></div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="panel">
            <h2>Review Queue</h2>
            <table class="data-table">
                <tr><th>Title</th><th>Status</th><th>Open</th></tr>
                <?php foreach ($reviewQueue as $article): ?>
                    <tr>
                        <td><?= e($article['title']) ?></td>
                        <td><?= e($article['status']) ?></td>
                        <td><a href="/geo_admin/?tab=articles&edit=<?= e((string) $article['id']) ?>">Review</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </section>
        <section class="panel">
            <h2>Published Output</h2>
            <table class="data-table">
                <tr><th>Title</th><th>Category</th><th>View</th></tr>
                <?php foreach (array_slice($publishedArticles, 0, 5) as $article): ?>
                    <tr>
                        <td><?= e($article['title']) ?></td>
                        <td><?= e($article['category_name']) ?></td>
                        <td><a href="/article.php?slug=<?= e($article['slug']) ?>" target="_blank">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </section>
    </div>
<?php elseif ($tab === 'models'): ?>
    <div class="split-grid">
        <section class="panel">
            <h2>Add Model</h2>
            <form method="post" class="stack-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_model">
                <label>Name <input type="text" name="name" required></label>
                <label>Provider <input type="text" name="provider_name" placeholder="OpenAI-compatible" required></label>
                <label>API URL <input type="text" name="api_url" placeholder="https://api.openai.com/v1" required></label>
                <label>Model ID <input type="text" name="model_id" placeholder="gpt-4.1-mini" required></label>
                <label>API Key <input type="password" name="api_key" required></label>
                <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <button class="button primary" type="submit">Save model</button>
            </form>
        </section>
        <section class="panel">
            <h2>Configured Models</h2>
            <table class="data-table">
                <tr><th>Name</th><th>Provider</th><th>Model</th><th>Endpoint</th></tr>
                <?php foreach ($models as $model): ?>
                    <tr><td><?= e($model['name']) ?></td><td><?= e($model['provider_name']) ?></td><td><?= e($model['model_id']) ?></td><td><?= e($model['api_url']) ?></td></tr>
                <?php endforeach; ?>
            </table>
            <p class="hint">Model generation uses OpenAI-style `/chat/completions`. If remote generation fails, GEOFlow falls back to the built-in writer.</p>
        </section>
    </div>
<?php elseif ($tab === 'assets'): ?>
    <div class="split-grid">
        <section class="panel">
            <h2>Create Asset</h2>
            <form method="post" class="stack-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_asset">
                <label>Type
                    <select name="asset_type">
                        <option value="title">Title</option>
                        <option value="keyword">Keyword</option>
                        <option value="image">Image</option>
                        <option value="knowledge">Knowledge Base</option>
                        <option value="prompt">Prompt</option>
                    </select>
                </label>
                <label>Title <input type="text" name="title" required></label>
                <label>Content <textarea name="content" rows="5" required></textarea></label>
                <button class="button primary" type="submit">Save asset</button>
            </form>
        </section>
        <section class="panel">
            <h2>Asset Library</h2>
            <table class="data-table">
                <tr><th>ID</th><th>Type</th><th>Title</th><th>Content</th></tr>
                <?php foreach ($assets as $asset): ?>
                    <tr><td><?= e((string) $asset['id']) ?></td><td><?= e($asset['asset_type']) ?></td><td><?= e($asset['title']) ?></td><td><?= e(mb_strimwidth($asset['content'], 0, 80, '...')) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </section>
    </div>
<?php elseif ($tab === 'tasks'): ?>
    <div class="split-grid">
        <section class="panel">
            <h2>Create Task</h2>
            <form method="post" class="stack-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_task">
                <label>Task Name <input type="text" name="name" required></label>
                <label>Model
                    <select name="model_id">
                        <option value="">Fallback generator</option>
                        <?php foreach ($models as $model): ?>
                            <option value="<?= e((string) $model['id']) ?>"><?= e($model['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Title Seed <input type="text" name="title_seed" required></label>
                <label>Primary Keyword <input type="text" name="primary_keyword" required></label>
                <label>Language
                    <select name="language_code">
                        <?php foreach (language_options() as $code => $label): ?>
                            <option value="<?= e($code) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Audience <input type="text" name="audience" value="General professional audience" required></label>
                <label>Category <input type="text" name="category_name" value="Insights" required></label>
                <label>Prompt Template <textarea name="prompt_template" rows="5">Write an original, structured article with clear sections, operational recommendations, and SEO metadata.</textarea></label>
                <label>Asset Attachments
                    <select name="asset_ids[]" multiple size="6">
                        <?php foreach ($assets as $asset): ?>
                            <option value="<?= e((string) $asset['id']) ?>"><?= e($asset['asset_type'] . ' - ' . $asset['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Publish Mode
                    <select name="publish_mode">
                        <option value="review">Draft to review</option>
                        <option value="auto">Auto publish</option>
                    </select>
                </label>
                <label>Schedule At <input type="datetime-local" name="schedule_at"></label>
                <button class="button primary" type="submit">Create task</button>
            </form>
        </section>
        <section class="panel">
            <h2>Task List</h2>
            <table class="data-table">
                <tr><th>ID</th><th>Name</th><th>Status</th><th>Keyword</th><th>Article</th><th>Action</th></tr>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= e((string) $task['id']) ?></td>
                        <td><?= e($task['name']) ?></td>
                        <td><?= e($task['status']) ?></td>
                        <td><?= e($task['primary_keyword']) ?></td>
                        <td><?= e((string) ($task['article_id'] ?? '')) ?></td>
                        <td>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="duplicate_task">
                                <input type="hidden" name="task_id" value="<?= e((string) $task['id']) ?>">
                                <button class="button ghost" type="submit">Duplicate</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <div class="action-row">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="run_scheduler">
                    <button class="button secondary" type="submit">Queue Due Tasks</button>
                </form>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="run_worker_once">
                    <button class="button primary" type="submit">Generate One Article</button>
                </form>
            </div>
        </section>
    </div>
<?php elseif ($tab === 'articles'): ?>
    <div class="split-grid">
        <section class="panel">
            <h2>Article Workflow</h2>
            <form method="get" class="filter-row">
                <input type="hidden" name="tab" value="articles">
                <select name="status">
                    <option value="">All statuses</option>
                    <option value="draft" <?= $articleFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="review" <?= $articleFilter === 'review' ? 'selected' : '' ?>>Review</option>
                    <option value="published" <?= $articleFilter === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="archived" <?= $articleFilter === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
                <button class="button ghost" type="submit">Filter</button>
            </form>
            <table class="data-table">
                <tr><th>Title</th><th>Status</th><th>Category</th><th>Actions</th></tr>
                <?php foreach ($filteredArticles as $article): ?>
                    <tr>
                        <td>
                            <a href="/article.php?slug=<?= e($article['slug']) ?>" target="_blank"><?= e($article['title']) ?></a>
                            <div class="muted-inline"><?= e($article['source_model']) ?></div>
                        </td>
                        <td><?= e($article['status']) ?></td>
                        <td><?= e($article['category_name']) ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="button ghost" href="/geo_admin/?tab=articles&edit=<?= e((string) $article['id']) ?>">Edit</a>
                                <form method="post" class="inline-form compact">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="article_transition">
                                    <input type="hidden" name="article_id" value="<?= e((string) $article['id']) ?>">
                                    <select name="status">
                                        <option value="draft">Draft</option>
                                        <option value="review">Review</option>
                                        <option value="published">Publish</option>
                                        <option value="archived">Archive</option>
                                    </select>
                                    <button class="button ghost" type="submit">Update</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </section>
        <section class="panel">
            <h2><?= $editingArticle ? 'Edit Article' : 'Review Guidance' ?></h2>
            <?php if ($editingArticle): ?>
                <form method="post" class="stack-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_article">
                    <input type="hidden" name="article_id" value="<?= e((string) $editingArticle['id']) ?>">
                    <label>Title <input type="text" name="title" value="<?= e($editingArticle['title']) ?>"></label>
                    <label>Summary <textarea name="summary" rows="4"><?= e($editingArticle['summary']) ?></textarea></label>
                    <label>Body HTML <textarea name="body_html" rows="14"><?= e($editingArticle['body_html']) ?></textarea></label>
                    <label>SEO Title <input type="text" name="seo_title" value="<?= e($editingArticle['seo_title']) ?>"></label>
                    <label>SEO Description <textarea name="seo_description" rows="3"><?= e($editingArticle['seo_description']) ?></textarea></label>
                    <label>Open Graph Title <input type="text" name="open_graph_title" value="<?= e($editingArticle['open_graph_title']) ?>"></label>
                    <label>Open Graph Description <textarea name="open_graph_description" rows="3"><?= e($editingArticle['open_graph_description']) ?></textarea></label>
                    <button class="button primary" type="submit">Save Article</button>
                </form>
            <?php else: ?>
                <p>Pick an article from the list to edit title, summary, HTML body, and SEO fields before publishing.</p>
                <ul class="admin-list">
                    <li>Use `review` for editorial QA.</li>
                    <li>Switch to `published` to surface the article on the frontend home page.</li>
                    <li>Use the edit view to refine metadata and on-page structure.</li>
                </ul>
            <?php endif; ?>
        </section>
    </div>
<?php elseif ($tab === 'settings'): ?>
    <div class="split-grid">
        <section class="panel">
            <h2>Site Settings</h2>
            <form method="post" class="stack-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_settings">
                <label>Site Name <input type="text" name="site_name" value="<?= e($settings['site_name'] ?? 'GEOFlow') ?>"></label>
                <label>Tagline <input type="text" name="site_tagline" value="<?= e($settings['site_tagline'] ?? '') ?>"></label>
                <label>Description <textarea name="site_description" rows="4"><?= e($settings['site_description'] ?? '') ?></textarea></label>
                <label>Theme
                    <select name="theme">
                        <option value="aurora" <?= ($settings['theme'] ?? '') === 'aurora' ? 'selected' : '' ?>>Aurora</option>
                        <option value="ember" <?= ($settings['theme'] ?? '') === 'ember' ? 'selected' : '' ?>>Ember</option>
                        <option value="sage" <?= ($settings['theme'] ?? '') === 'sage' ? 'selected' : '' ?>>Sage</option>
                    </select>
                </label>
                <label>Auto Publish
                    <select name="auto_publish">
                        <option value="false" <?= ($settings['auto_publish'] ?? 'false') === 'false' ? 'selected' : '' ?>>Disabled</option>
                        <option value="true" <?= ($settings['auto_publish'] ?? 'false') === 'true' ? 'selected' : '' ?>>Enabled</option>
                    </select>
                </label>
                <button class="button primary" type="submit">Save settings</button>
            </form>
        </section>
        <section class="panel">
            <h2>Theme Preview</h2>
            <div class="theme-preview-grid">
                <div class="theme-preview aurora">Aurora</div>
                <div class="theme-preview ember">Ember</div>
                <div class="theme-preview sage">Sage</div>
            </div>
            <p class="hint">Saving a theme changes the public frontend palette immediately.</p>
        </section>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$title = 'GEOFlow Admin';
require BASE_PATH . '/includes/templates/admin_layout.php';
