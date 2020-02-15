# Kirby 3 Handlebars

![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-handlebars?color=ae81ff)
![Stars](https://flat.badgen.net/packagist/ghs/bnomei/kirby3-handlebars?color=272822)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby3-handlebars?color=272822)
![Issues](https://flat.badgen.net/packagist/ghi/bnomei/kirby3-handlebars?color=e6db74)
[![Build Status](https://flat.badgen.net/travis/bnomei/kirby3-handlebars)](https://travis-ci.com/bnomei/kirby3-handlebars)
[![Coverage Status](https://flat.badgen.net/coveralls/c/github/bnomei/kirby3-handlebars)](https://coveralls.io/github/bnomei/kirby3-handlebars)
[![Maintainability](https://flat.badgen.net/codeclimate/maintainability/bnomei/kirby3-handlebars)](https://codeclimate.com/github/bnomei/kirby3-handlebars) 
[![Demo](https://flat.badgen.net/badge/website/examples?color=f92672)](https://kirby3-plugins.bnomei.com/handlebars) 
[![Gitter](https://flat.badgen.net/badge/gitter/chat?color=982ab3)](https://gitter.im/bnomei-kirby-3-plugins/community) 
[![Twitter](https://flat.badgen.net/badge/twitter/bnomei?color=66d9ef)](https://twitter.com/bnomei)

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
- Prepare data in 
    - A) controllers stored at `site/controllers/*` to be available in the templates or 
    - B) use a model and implement `handlebarsData(): array` or
    - C) use a model, extend from `\Bnomei\HandlebarsPage` and define exported data with `public static $handlebarsData = [];`
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

**Data provider A: site/controllers/home.php**
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

**Data provider B with handlebarsData() method: site/models/home.php**
```php
<?php
class HomePage extends Page
{
    public function handlebarsData(): array
    {
        return [
            'title' => $this->title(), // Home
            'c'=> 'Cassia',
            'counting' => [
                ['label' => 1],
                ['label' => 2],
                ['label' => 3],
            ],
        ];
    }
}
```

**Data provider C extending HandlebarsPage Class: site/models/home.php**
```php
<?php
class HomePage extends \Bnomei\HandlebarsPage
{
    public static $handlebarsData = [
        'title',    // $this->title()
        'c',        // $this->c()
        'counting'  // $this->counting()
    ];

    public function c(): string
    {
        return 'Cassia';
    }
 
    public function counting(): array
    {
        return [
            ['label' => 1],
            ['label' => 2],
            ['label' => 3],
        ];
    }
}
```

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

### Queries

**default queries**

The plugin has a few queries built in that you can directly use in your handlebar templates. You can override these in setting the `bnomei.handlebars.queries` option.

```handlebars
{{ site.title }}
{{ site.url }}
{{ page.title }}
{{ page.url }}
{{ page.slug }}
{{ page.template }}
```

**dynamic queries**

You can also use queries when providing data from controllers or models.

> HINT: This allows you to write queries in textarea fields as well!

```php
return [
    'textWithQuery' => "Some field value {{ page.myfield }} at {{ page.date.toDate('c') }}",
    'kirbytextWithQuery' => $page->text()->kirbytext(),
];
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
    'bnomei.handlebars.render' => true, // default: false        
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

| bnomei.handlebars.        | Default        | Description               |            
|---------------------------|----------------|---------------------------|
| component | `true` | if `false` no templating will be handled by this plugin and you need to use the `hbs()`/`handlebars()` functions. |
| no-escape | `true` | By default data sent to template [will NOT be escaped](https://zordius.github.io/HandlebarsCookbook/LC-FLAG_NOESCAPE.html). This way your templates can render data formated as html. You can use Kirbys Field Methods `$field->kirbytext()`, `$field->html()` or the `Kirby\Toolkit\Str`-Class functions to escape your text properly. Alternatively you can set it to `false` and use `{{{ var }}}` [triple mustaches](https://handlebarsjs.com/expressions.html). |
| dir-templates | `callback`  | returning `kirby()->roots()->templates()` |
| dir-partials | `callback`  | returning `kirby()->roots()->templates().'/partials'` |
| extension-input | `hbs` | |
| extension-output | `lnc` | hbs compiled to php |
| render | `false` | cache render based on hash of data |
| files | `true` | cache paths of template and partial files |
| lnc | `true` | cache compiled php |
| queries | `[...]` | an array of predefined queries you can use in your templates |

## Dependencies

- [LightnCandy](https://github.com/zordius/lightncandy)

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-handlebars/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

