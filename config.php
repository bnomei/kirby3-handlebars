<?php

Kirby::plugin('bnomei/handlebars', [
    'options' => [
        'component' => false,
        'partials' => false, // true or name of folder
        'extension' => 'hbs', // or 'mustache' etc.
        'escape' => false, // => FLAG_NOESCAPE
        'cache.partials' => true,
        'cache.render' => true,
    ],
    'components' => [
        'template' => function (Kirby\Cms\App $kirby, string $name, string $type = 'html') {
            if (option('bnomei.handlebars.component')) {
                return new Bnomei\Handlebars($name, $type);
            }
            return new  Kirby\Cms\Template($name, $type);
        }
    ],
    'snippets' => [
        'plugin-handlebars' => __DIR__ . '/snippets/handlebars.php',
        'handlebars/render' => __DIR__ . '/snippets/handlebars/render.php',
    ],
    'pageMethods' => [
        'handlebars' => function ($template = null, $data = []) {
            return snippet('plugin-handlebars', [
                'template' => ($template ? $template : $this->template()), 
                'data' => $this->controller($data)
            ], true);
        }
    ]
]);
