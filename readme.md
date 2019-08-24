# Kirby 3 Handlebars

![GitHub release](https://img.shields.io/github/release/bnomei/kirby3-handlebars.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg) ![Kirby 3 Pluginkit](https://img.shields.io/badge/Pluginkit-YES-cca000.svg) [![Build Status](https://travis-ci.com/bnomei/kirby3-handlebars.svg?branch=master)](https://travis-ci.com/bnomei/kirby3-handlebars) [![Coverage Status](https://coveralls.io/repos/github/bnomei/kirby3-handlebars/badge.svg?branch=master)](https://coveralls.io/github/bnomei/kirby3-handlebars?branch=master) [![Gitter](https://badges.gitter.im/bnomei-kirby-3-plugins/community.svg)](https://gitter.im/bnomei-kirby-3-plugins/community?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

Kirby 3 Component for semantic templates with [Handlebars](https://handlebarsjs.com/) and [Mustache](https://mustache.github.io/)

## Commercial Usage

This plugin is free but if you use it in a commercial project please consider to 
- [make a donation 🍻](https://www.paypal.me/bnomei/5) or
- [buy me ☕](https://buymeacoff.ee/bnomei) or
- [buy a Kirby license using this affiliate link](https://a.paddle.com/v2/click/1129/35731?link=1170)

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby3-handlebars/archive/master.zip) as folder `site/plugins/kirby3-handlebars` or
- `git submodule add https://github.com/bnomei/kirby3-handlebars.git site/plugins/kirby3-handlebars` or
- `composer require bnomei/kirby3-handlebars`

## Usage as Template Component

- Put your handlebars templates in the `site/templates/` folder.
- Prepare data in controllers stored at `site/controllers/*` to be available in the templates.
- In case you do not have a handlebar template with matching name it will fallback to the required `default.hbs` file.

**content/home/home.txt**
```markdown
Title: Home
```

**site/templates/default.hbs**
```handlebars
{{ title }} of <i>{{ c }}</i>.
{{#counting}} <br> - {{ label }} {{/counting}}
```

**site/controllers/home.php**
```php
<?php
return function ($site, $page, $kirby) {
    return [
        'title' => $page->title(), // Home
        'c'=> 'Cassia',
        'counting' => [
            ['label' => 1],
            ['label' => 2],
            ['label' => 3],
        ],
    ];
};
```
> Note: `kirby`, `site`, `pages` and `page` can **not** be used as top-level data keys. They will be overwritten by Kirby Objects and later pruned by the plugin for serialization.

**http://localhost:80/**
```html
Home of <i>Cassia</i>
<br>- 1
<br>- 2
<br>- 3
```

### Partials

Partials are stored at `/site/template/partials`. Think of them as reusable blocks injected into your handlebars templates **before** they get compiled. That is why partials need no cache files.

**/content/pizza/call-a-partial.txt**
```markdown
Title: 🍕
```

**/site/templates/call-a-partial.hbs**
```handlebars
{{> @piece-of-cake this }}
```
> Note: `@` and `this` are used by the JS version of Handlebars in Fractal to denote a partial and forward the data. Both will be automatically removed on PHP compilation by this plugin.

**/site/templates/partials/piece-of-cake.hbs**
```handlebars
Piece of {{ cake }}
```

**/site/controllers/call-a-partial.php**
```php
<?php
return function ($site, $page, $kirby) {
    return ['cake' => $page->title()];
};
```

**http://localhost:80/pizza**
```html
Piece of 🍕
```

## Rapid Localhost Development

See [tests](https://github.com/bnomei/kirby3-handlebars/tree/master/tests/fractal) on how to install [Fractal](https://fractal.build/) and [point it](https://fractal.build/guide/project-settings.html#the-fractal-js-file) to the the `/site/templates`-folder.

```
cd test/fractal;
npm install;
npm run dev;
```

Or create a [static build](https://github.com/bnomei/kirby3-handlebars/tree/master/ui)

```
npm run build;
```

## Usage without Component

Maybe you just need to parse some handlebars and not use the template component. Disable the component with the `bnomei.handlebars.component` config setting and just use the provided helper functions. The `hbs()`/`handlebars()` take the same parameters like the Kirby  `snippet()` function.

**templates/render-unto.hbs**
```handlebars
Render unto {{ c }} the things that are {{ c }}'s, and unto {{ g }} the things that are {{ g }}'s.
```

**templates/default.php**
```php
<?php
    // echo template 'render-unto'
    // data from site/controllers/home.php merged with custom array
    hbs('render-unto', ['c'=> 'Caesar', 'g'=>'God']); 

    // return template 'render-unto'
    $string = hbs('render-unto', ['c' => 'Cat', 'g' => 'Dog'], true);
```

> Render unto Caesar the things that are Caesar's, and unto God the things that are God's.
> Render unto Cat the things that are Cat's, and unto Dog the things that are Dog's.

## Performance & Caching

LightnCandy is extremely fast and lightweight since its compiled to raw PHP just like [Blade Templates](https://github.com/search?q=kirby-cms+blade). Templates are only precompiled to native PHP on modification. This could be disabled with the `lnc` setting but you really should not.

### Build-In Cache

Render output is **not** cached by default. You can activate it using the `render` setting. The render cache is devalidated if either template or data is modified. But since a hash of the data is created every time this has a slight performance impact.

**site/config.php**
```php
<?php
return [
    'bnomei.handlebars.cache.render' => true, // default: false        
];
```

### Lapse Plugin

- A faster and more robust solution would be to utilize the [Lapse Plugin](https://github.com/bnomei/kirby3-lapse) inside the controllers. 
- It can automatically and efficiently take care of tracking the modification of Kirby Objects **without** creating a data hash. 
- Further more Kirby Objects will automatically be stored as array/string/int/float/bool/null. For example you do not have to call `->value()` on every field.

**controllers/home.php**
```php
<?php
return function ($site, $page, $kirby) {

    $collection = collection('mycollection');
    $data = Lapse::io(
        // will create key from array of strings and/or objects
        ['myhash', $page, $page->children()->images(), $collection, $site],

        // this will only executed on modification 
        function () use ($site, $page, $collection) {
            return [
                // will not break serialization => automatically store ->value() of fields
                'author' => $site->author(),
                'title' => $page->title(),
                'hero' => $page->children()->images()->first()->srcset()->html(),
                'count' => $collection->count(),
            ];
        }
    );
    return $data;
};
```

## Settings

All settings need to be prefixed with `bnomei.handlebars.`

#### component
- default: `false` 
if `true` all templating will be handled by this plugin.

#### no-escape
- default: `true`
By default data sent to template [will NOT be escaped](https://zordius.github.io/HandlebarsCookbook/LC-FLAG_NOESCAPE.html). This way your templates can render data formated as html. You can use Kirbys Field Methods `$field->kirbytext()`, `$field->html()` or the `Kirby\Toolkit\Str`-Class functions to escape your text properly.
Alternatively you can set it to `false` and use `{{{ var }}}` [triple mustaches](https://handlebarsjs.com/expressions.html).

#### dir-templates
- default: callback returning `kirby()->roots()->templates()`

#### dir-partials
- default: callback returning `kirby()->roots()->templates().'/partials'`

#### extension-input
- default: `hbs`

#### extension-output
- default: `lnc`, hbs compiled to php

#### render
- default: `false`, cache render based on hash of data

#### files
- default: `true`, cache paths of template and partial files

#### lnc
- default: `true`, cache compiled php

## Dependencies

- [LightnCandy](https://github.com/zordius/lightncandy)

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-handlebars/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

