<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

$tasks = app('tasks')->dueTasks();

foreach ($tasks as $task) {
    app('queue')->enqueue('generate_article', [
        'task_id' => $task['id'],
    ], (int) $task['id']);
    app('tasks')->markQueued((int) $task['id']);
    cli_log('Queued task #' . $task['id'] . ' - ' . $task['name']);
}

if ($tasks === []) {
    cli_log('No due tasks found.');
}

