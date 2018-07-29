# Kirby 3 Handlebars

![GitHub release](https://img.shields.io/github/release/bnomei/kirby3-handlebars.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg)

Kirby 3 Component, Snippet and Page Method for semantic templates with [Handlebars](https://handlebarsjs.com/) and [Mustache](https://mustache.github.io/)

This plugin is free but if you use it in a commercial project please consider to [make a donation 🍻](https://www.paypal.me/bnomei/5).

## Dependencies

- [LightnCandy](https://github.com/zordius/lightncandy)

## Performance

- LightnCandy is extremely fast and lightweight.
- Templates are only precompiled to native PHP on modification.
- Render output is cached even without Kirbys Page Cache. The render cache is devalidated if template or data is modified.

## Usage without Component

**templates/caesar.hbs**
```
Render unto {{ c }} the things that are {{ c }}'s, and unto {{ g }} the things that are {{ g }}'s.
```

**templates/home.php**
```php
  snippet('plugin-handlebars', [
    'template' => 'home',
    'data' => [
        'c' => 'Caesar', 
        'g' => 'God'
    ]
  ]);

  // or
  echo $page->template(); // 'home' for example
  echo $page->handlebars(); // template of page as string 'home', data from site/controllers/home.php
  echo $page->handlebars('caesar', ['c'=>'nero', 'g'=>'fire']); // template 'caesar', data from site/controllers/home.php merged with custom array
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

**extension**
- default: `hbs`

**escape**
- default: `false`
By default data sent to template [will NOT be escaped](https://zordius.github.io/HandlebarsCookbook/LC-FLAG_NOESCAPE.html). This way your templates can render data formates as html. You can use Kirbys Field Methods `$field->kirbytext()`, `$field->html()` or the `Kirby\Toolkit\Str`-Class functions to escape your text properly.

**partials**
- default: `true`
By default all partials in `site/templates/partials` will be loaded. You can change the subfolder by setting a string to **partials**.

> Note: Partials support is still ALPHA status!

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-handlebars/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

