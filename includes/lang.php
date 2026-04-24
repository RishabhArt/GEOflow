<?php

declare(strict_types=1);

function translations(): array
{
    static $items;
    if ($items !== null) {
        return $items;
    }

    $items = [
        'en' => [
            'brand' => 'GEOFlow',
            'tagline' => 'Generative content operations for GEO and SEO',
            'admin' => 'Admin',
            'articles' => 'Articles',
            'tasks' => 'Tasks',
            'assets' => 'Assets',
            'models' => 'Models',
        ],
        'zh-CN' => [
            'brand' => 'GEOFlow',
            'tagline' => '面向 GEO 与 SEO 的生成式内容运营系统',
            'admin' => '管理后台',
            'articles' => '文章',
            'tasks' => '任务',
            'assets' => '资产',
            'models' => '模型',
        ],
        'ja' => [
            'brand' => 'GEOFlow',
            'tagline' => 'GEO / SEO 向け生成コンテンツ運用システム',
            'admin' => '管理',
            'articles' => '記事',
            'tasks' => 'タスク',
            'assets' => 'アセット',
            'models' => 'モデル',
        ],
        'es' => [
            'brand' => 'GEOFlow',
            'tagline' => 'Operaciones de contenido generativo para GEO y SEO',
            'admin' => 'Administración',
            'articles' => 'Artículos',
            'tasks' => 'Tareas',
            'assets' => 'Recursos',
            'models' => 'Modelos',
        ],
        'ru' => [
            'brand' => 'GEOFlow',
            'tagline' => 'Система генеративного контент-операционного цикла для GEO и SEO',
            'admin' => 'Админка',
            'articles' => 'Статьи',
            'tasks' => 'Задачи',
            'assets' => 'Материалы',
            'models' => 'Модели',
        ],
    ];

    return $items;
}

function t(string $key, ?string $locale = null): string
{
    $locale = $locale ?? active_locale();
    $items = translations();
    return $items[$locale][$key] ?? $items['en'][$key] ?? $key;
}

