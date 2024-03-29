<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('bnomei/handlebars', [
    'options' => [
        'component' => true,
        'compile-flags' => function () {
            // https://zordius.github.io/HandlebarsCookbook/9900-lc-options.html
            return \LightnCandy\LightnCandy::FLAG_ELSE
                | \LightnCandy\LightnCandy::FLAG_NOESCAPE
                // | \LightnCandy\LightnCandy::FLAG_PARENT
                // | \LightnCandy\LightnCandy::FLAG_RUNTIMEPARTIAL
                // | \LightnCandy\LightnCandy::FLAG_NAMEDARG
            ;
        },

        'dir-templates' => function () {
            return kirby()->roots()->templates();
        },
        'dir-partials' => function () {
            $templates = option('bnomei.handlebars.dir-templates');
            if (is_callable($templates)) {
                $templates = $templates();
            }
            return $templates . DIRECTORY_SEPARATOR . 'partials';
        },

        'extension-input' => 'hbs', // or 'mustache' etc.
        'extension-output' => 'lnc',
        'extension-kql' => 'json',

        // ALLOW creation...
        'cache' => true, // to get the root folder
        'cache.render' => true, // creates a plugin cache called 'render'
        'cache.files' => true, // creates a plugin cache called 'files'
        'cache.lnc' => true, // creates a plugin cache called 'lnc'
        // actually used config
        'render' => false,
        'files' => true,
        'lnc' => true,

        'queries' => [
            'site.title',
            'site.url',
            'page.autoid',
            'page.title',
            'page.text',
            'page.url',
            'page.slug',
            'page.template',
        ],
    ],
    'components' => [
        'template' => function (Kirby\Cms\App $kirby, string $name, string $type = 'html', string $defaultType = 'html') {
            if (option('bnomei.handlebars.component')) {
                return new Bnomei\HandlebarsTemplate($name, $type);
            }
            return new Kirby\Cms\Template($name, $type);
        },
    ],
    'snippets' => [
        'handlebars/render' => __DIR__ . '/snippets/handlebars/render.php',
    ],
    'pageMethods' => [
        'handlebars' => function ($template = null, $data = []) {
            return (new \Bnomei\Handlebars())->render(
                $template ?? $this->template(),
                $this->controller($data),
                null,
                null,
                true
            );
        },
    ],
]);

if (!class_exists('Bnomei\Handlebars')) {
    require_once __DIR__ . '/classes/Handlebars.php';
}

if (!function_exists('handlebars')) {
    function handlebars(string $template, $data = [], $return = false): ?string
    {
        return (new \Bnomei\Handlebars())->render($template, $data, null, null, $return);
    }
}

if (!function_exists('hbs')) {
    function hbs(string $template, $data = [], $return = false): ?string
    {
        return (new \Bnomei\Handlebars())->render($template, $data, null, null, $return);
    }
}
