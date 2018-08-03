<?php

namespace Bnomei;

use LightnCandy\LightnCandy;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Dir;

class Handlebars extends \Kirby\Cms\Template
{
    public function extension(): string
    {
        return (string) option('bnomei.handlebars.extension');
    }

    public function render(array $data = []): string
    {
        return static::r($this->name(), $data, $this->root(), $this->file());
    }

    public static function r($name, array $data = [], $root = null, $file = null): string
    {
        $debug = option('debug');
        $escape = option('bnomei.handlebars.escape');
        $partials = option('bnomei.handlebars.partials');
        if(is_bool($partials) && $partials) {
            $partials = 'partials';
        }
        if(!$root) {
            $root = kirby()->roots()->templates();
        }
        if(!$file) {
            $file = F::realpath($root . '/' . $name . '.' . option('bnomei.handlebars.extension'), $root);
        }
        $partialsRoot = '';
        if(is_string($partials)) {
            $partialsRoot = $root . DIRECTORY_SEPARATOR . $partials;
            Dir::make($partialsRoot);
        }
        $dir = $root . DIRECTORY_SEPARATOR . 'cache';
        Dir::make($dir);
        $cache = $dir . DIRECTORY_SEPARATOR . ($debug?'.':'') . $name . '.php';
        $php = null;
        $needsUpdate = false;

        // COMPILE OPTIONS
        $compileOptions = [
            'flags' => LightnCandy::FLAG_NOESCAPE
        ];
        if($debug) {
            $compileOptions = [
                'flags' => LightnCandy::FLAG_NOESCAPE | LightnCandy::FLAG_RENDER_DEBUG,
                'renderex' => '// Compiled at ' . date('Y-m-d h:i:s'),
                'prepartial' => function ($context, $template, $name) {
                    return "<!-- partial: $template -->";
                },
            ];
            kirby()->cache('bnomei.handlebars.partials')->flush();
            kirby()->cache('bnomei.handlebars.render')->flush();
        }

        // PARTIALS
        if($partials) {
            $modified = kirby()->cache('bnomei.handlebars.partials');
            $partialModified = false;
            $parts = [];
            foreach(Dir::read($partialsRoot) as $p) {
                $fm = F::modified($p);
                if($m = $modified->get($p)) {
                    if($m < $fm) {
                        $partialModified = true;
                    }
                }
                $modified->set($p, $fm);
                $pi = pathinfo($p, PATHINFO_FILENAME);
                $parts[$pi] = F::read($p);
            }
            $compileOptions['partials'] = $parts;
            if($partialModified) {
                Dir::remove($dir, true); // flush
            }
        }

        // RENDER
        try {
            // file cache since kirby\cache will not work with include
            if(!F::exists($cache) || (F::exists($cache) && F::modified($cache) < F::modified($file))) {
                $t = F::read($file);
                $php = LightnCandy::compile($t, $compileOptions);
                F::write($cache, $php);
                $needsUpdate = true;
            } else {
                $php = F::read($cache);
            }

            $renderCache = kirby()->cache('bnomei.handlebars.render');
            $rid = md5(($debug?'.':'').$name.md5(json_encode($data)));
            $result = $renderCache->get($rid);
            if (!$result || $needsUpdate) {
                // NOTE: since LightnCandy returns a Closure and
                // these can not be packed into a var as string
                // a snippet is used to echo rendering
                $result = snippet('handlebars/render', [
                    'precompiledTemplate' => $php,
                    'data' => $data,
                    ]);
                $renderCache->set($rid, $result);
            }
        } catch(Exception $ex) {
            return $ex->getMessage();
        }
        return '';
    }
}
