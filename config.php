<?php

Kirby::plugin('bnomei/handlebars', [
    'options' => [
        'component' => false,
        'no-escape' => true, // => FLAG_NOESCAPE aka {{{ }}} are not needed

        'dir.templates' => function () {
            return kirby()->roots()->templates();
        },
        'dir.partials' => function () {
            $templates = option('bnomei.handlebars.dir.templates');
            if (is_callable($templates)) {
                $templates = $templates();
            }
            return $templates . DIRECTORY_SEPARATOR . 'partials';
        },

        'extension.input' => 'hbs', // or 'mustache' etc.
        'extension.output' => 'lnc',

        'cache.render' => true, // creates a plugin cache called 'render'
        'cache.files' => true, // creates a plugin cache called 'files'
        'cache.lnc' => true, // creates a plugin cache called 'lnc'
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
