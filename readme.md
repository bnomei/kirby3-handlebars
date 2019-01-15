# Kirby 3 Handlebars

![GitHub release](https://img.shields.io/github/release/bnomei/kirby3-handlebars.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg)

Kirby 3 Component, Snippet and Page Method for semantic templates with [Handlebars](https://handlebarsjs.com/) and [Mustache](https://mustache.github.io/)

## Commercial Usage

This plugin is free but if you use it in a commercial project please consider to 
- [make a donation 🍻](https://www.paypal.me/bnomei/3) or
- [buy me ☕](https://buymeacoff.ee/bnomei) or
- [buy a Kirby license using this affiliate link](https://a.paddle.com/v2/click/1129/35731?link=1170)

## Installation

- for devkit-setup use `composer require bnomei/kirby3-handlebars` or
- extract latest release of [kirby3-handlebars.zip](https://github.com/bnomei/kirby3-handlebars/releases/download/v2.0.0/kirby3-handlebars.zip) as folder `site/plugins/kirby3-handlebars`

> Installation as a gitsubmodule is *not* supported.


## Dependencies

- [LightnCandy](https://github.com/zordius/lightncandy)

## Performance

- LightnCandy is extremely fast and lightweight.
- Templates are only precompiled to native PHP on modification.
- Render output is cached even without Kirbys Page Cache. The render cache is devalidated if either template or data is modified.

## Usage without Component

**templates/render-unto.hbs**
```
Render unto {{ c }} the things that are {{ c }}'s, and unto {{ g }} the things that are {{ g }}'s.
```

**templates/home.hbs**
```
{{ page.title }} of <i>{{ c }}</i>.
```

**controllers/home.php**
```
<?php
return function ($site, $page, $kirby) {
  return ['c'=>'Cassia', 'g'=>null];
};
```

**templates/home.php**
```php
<?php
  snippet('plugin-handlebars', [
    'template' => 'render-unto',
    'data' => [
        'c' => 'Caesar', 
        'g' => 'God'
    ]
  ]);
  // => Render unto Caesar the things that are Caesar's, and unto God the things that are God's.

  // or
  echo $page->template();
  // => home

  echo $page->handlebars(); // template of page as string 'home', data from site/controllers/home.php
  // => Home of <i>Cassia</i>.
  
  echo $page->handlebars('render-unto', ['g'=>'Gods']); // template 'render-unto', data from site/controllers/home.php merged with custom array
  // => Render unto Cassia the things that are Cassia's, and unto God the things that are God's.
```

> TIP: you can also get the output in a variable setting snippets `return`-param to true. `$output = snippet(..., [...], true)`;

## Usage as Template Component

- Put your handlebars templates in the `site/templates/` folder.
- See below on how turn on Component and configure File extension in settings.
- Prepare data in Controllers stored at `site/controllers/*` which will be available in the templates
- In case you do not have a handlebar template with matching name it will fallback to kirbys php template logic.

## Settings

**component**
- default: `false` 
if `true` all templating will be handled by this plugin.

**no-escape**
- default: `true`
By default data sent to template [will NOT be escaped](https://zordius.github.io/HandlebarsCookbook/LC-FLAG_NOESCAPE.html). This way your templates can render data formated as html. You can use Kirbys Field Methods `$field->kirbytext()`, `$field->html()` or the `Kirby\Toolkit\Str`-Class functions to escape your text properly.
Alternatively you can set it to `false` and use `{{{ var }}}` [triple mustaches](https://handlebarsjs.com/expressions.html).

**dir.templates**
- default: callback returning `kirby()->roots()->templates()`

**dir.partials**
- default: callback returning `kirby()->roots()->templates().'/partials'`

**extension.input**
- default: `hbs`

**extension.output**
- default: `lnc`, hbs compiled to php

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-handlebars/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

