<?php

namespace Bnomei;

use LightnCandy\LightnCandy;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Dir;

class Handlebars extends \Kirby\Cms\Template
{
    public function extension(): string
    {
        return (string) option('bnomei.handlebars.extension.output');
    }

    public function file(): string
    {
        $output = self::loadTemplate($this->name())['output'];
        return $output;
    }

    public function render(array $data = []): string
    {
        return static::r($this->name(), $data, $this->root(), $this->file());
    }

    private static $compileOptions = null;
    private static function compileOptions()
    {
        if (self::$compileOptions) {
            return self::$compileOptions;
        }

        $flags = LightnCandy::FLAG_ELSE;
        if (option('bnomei.handlebars.no-escape')) {
            $flags |= LightnCandy::FLAG_NOESCAPE;
        }
        // NOTE: current will not notice change between debug and not. l'n'c debug is not supported here yet.
        // if (option('debug')) {
        //     $flags |= LightnCandy::FLAG_RENDER_DEBUG;
        // }
        self::$compileOptions = [
            'flags' => $flags,
            'partialresolver' => function ($cx, $name) {
                $string = self::loadTemplate($name)['string'];
                if (!$string) {
                    $string = "[partial ($name) not found]";
                }
                return $string;
            }
        ];
        // if (option('debug')) {
        //     self::$compileOptions = array_merge(self::$compileOptions, [
        //         'renderex' => '// Compiled at ' . date('Y-m-d h:i:s'),
        //         'prepartial' => function ($context, $template, $name) {
        //             return "<!-- partial: $name -->".$template;
        //         },
        //     ]);
        // }
        return self::$compileOptions;
    }

    private static function templateInput($filename, $force = false)
    {
        $filePath = null;
        $isPartial = false;
        $inputExtension = '.' . option('bnomei.handlebars.extension.input');

        if ($force || option('debug')) {
            kirby()->cache('bnomei.handlebars.files')->flush();
        }

        $filesCache = kirby()->cache('bnomei.handlebars.files');
        $filesPartials = $filesCache->get('partials');
        if (!$filesPartials) {
            $dirPartials = option('bnomei.handlebars.dir.partials');
            if (is_callable($dirPartials)) {
                $dirPartials = $dirPartials();
            }
            $filesPartials = \Kirby\Toolkit\Dir::index($dirPartials, true, null, $dirPartials);
            $filesCache->set('partials', $filesPartials);
        }
        $filesTemplates = $filesCache->get('templates');
        if (!$filesTemplates) {
            $dirTemplates = option('bnomei.handlebars.dir.templates');
            if (is_callable($dirTemplates)) {
                $dirTemplates = $dirTemplates();
            }
            $filesTemplates = \Kirby\Toolkit\Dir::index($dirTemplates, true, null, $dirTemplates);
            $filesCache->set('templates', $filesTemplates);
        }

        // Partials
        foreach ($filesPartials as $partial) {
            if (is_file($partial) && basename($partial, $inputExtension) == $filename) {
                $filePath = $partial;
                $isPartial = true;
                break;
            }
        }

        // Templates
        if (!$filePath) {
            foreach ($filesTemplates as $template) {
                if (is_file($template) && basename($template, $inputExtension) == $filename) {
                    $filePath = $template;
                    break;
                }
            }
        }

        if (!$filePath && !$force) {
            // not found force cache update and try again once
            return self::templateInput($filename, true);
        }

        return compact('filePath', 'isPartial', 'filename');
    }

    private static function templateOutput($filename, $isPartial = false)
    {
        if ($isPartial) {
            $filename = '@' . $filename;
        }
        return kirby()->roots()->cache() . DIRECTORY_SEPARATOR .
            'bnomei' . DIRECTORY_SEPARATOR . 'handlebars' . DIRECTORY_SEPARATOR . 'lnc' . DIRECTORY_SEPARATOR .
            $filename . '.' .option('bnomei.handlebars.extension.output');
    }

    private static $templates = [];
    public static function loadTemplate($template)
    {
        $template = str_replace('@', '', $template);
        $string = null;
        $needsUpdate = false;
        $inputData = self::templateInput($template);
        $input = $inputData['filePath'];
        if (!$input) {
            return compact('string', 'needsUpdate', 'inputData');
        }
        $isPartial = $inputData['isPartial'];
        $output = self::templateOutput($template, $isPartial);

        // TODO: what if partial changed but template did not?

        // file cache since kirby\cache will not work with include
        if (option('debug') || !F::exists($output) || (F::exists($output) && F::modified($output) < F::modified($input))) {
            $t = F::read($input);

            // fix fractal.build partial syntax
            $string = \Kirby\Toolkit\Str::replace($t, '{{> @', '{{> ');
            if (!$isPartial) {
                $compileOptions = self::compileOptions();
                $string = LightnCandy::compile($t, $compileOptions);
            }
            F::write($output, $string);
            $templates[$output] = $string;
            $needsUpdate = true;
        } else {
            $string = \Kirby\Toolkit\A::get($templates, $output);
            if (!$string) {
                $string = F::read($output);
                $templates[$output] = $string;
            }
        }

        return compact('string', 'needsUpdate', 'input', 'output');
    }

    public static function r($name, array $data = [], $root = null, $file = null): string
    {
        $result = null;
        $renderCacheId = null;
        $ext = '.' . option('bnomei.handlebars.extension.output');
        $template = $file ? basename($file, $ext) : $name;

        // remove objects to allow json serialization
        foreach (['kirby', 'site', 'pages', 'page'] as $k) {
            if (\Kirby\Toolkit\A::get($data, $k)) {
                unset($data[$k]);
            }
        }


        try {
            if (option('bnomei.handlebars.cache.render')) {
                $renderCache = kirby()->cache('bnomei.handlebars.render');

                // https://stackoverflow.com/questions/3665247/fastest-hash-for-non-cryptographic-uses#3665527
                $renderCacheId = $template . '-' . crc32(json_encode($data));
                if (option('debug')) {
                    $renderCache->flush();
                } else {
                    $result = $renderCache->get($renderCacheId);
                }
            }

            $loadTemplate = self::loadTemplate($template);
            $php = $loadTemplate['string'];
            if (!$php) {
                return '';
            }

            if (!$result || $loadTemplate['needsUpdate']) {
                // NOTE: since LightnCandy returns a Closure and
                // these can not be packed into a var as string
                // a snippet is used to echo rendering

                $result = snippet('handlebars/render', [
                    'precompiledTemplate' => $php,
                    'data' => $data,
                ], true);

                if ($renderCacheId) {
                    $renderCache->set($renderCacheId, $result);
                }
            }
        } catch (Exception $ex) {
            return $ex->getMessage();
        }

        return $result ? $result : '';
    }
}
