<?php

declare(strict_types=1);

namespace Bnomei;

use Exception;
use Kirby\Cms\Field;
use Kirby\Cms\Page;
use Kirby\Data\Json;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Dir;
use Kirby\Toolkit\Str;

final class Handlebars
{
    /*
     * @var string
     */
    private $renderCacheId;

    /*
     * @var array
     */
    private $lncFiles;

    /**
     * Handlebars constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'debug' => option('debug'),
            'extension-output' => option('bnomei.handlebars.extension-output'),
            'extension-input' => option('bnomei.handlebars.extension-input'),
            'extension-kql' => option('bnomei.handlebars.extension-kql'),
            'queries' => option('bnomei.handlebars.queries'),
            'render' => option('bnomei.handlebars.render'),
        ];
        $this->options = array_merge($defaults, $options);
        $this->options['render'] = $this->options['render'] && !$this->options['debug'];

        foreach ($this->options as $key => $call) {
            if (!is_string() && is_callable($call) && !in_array($call, ['hbs', 'handlebars', 'html'])) {
                $this->options[$key] = $call();
            }
        }

        $this->lncFiles = LncFiles::singleton($this->options);

        if ($this->option('debug')) {
            try {
                kirby()->cache('bnomei.handlebars.render')->flush();
            } catch (Exception $e) {
                //
            }
        }
    }

    /**
     * @param null $key
     * @return array|mixed
     */
    public function option($key = null)
    {
        if ($key) {
            return A::get($this->options, $key);
        }
        return $this->options;
    }

    /**
     * @param $file
     * @return string
     */
    public function name($file): string
    {
        $name = basename($file, '.' . $this->option('extension-input'));
        $name = str_replace('@', '', $name);
        return $name;
    }

    /**
     * @param $name
     * @return string
     * @throws InvalidArgumentException
     */
    public function file($name): string
    {
        return $this->lncFiles->hbsFile($name);
    }

    private function array_map_recursive($arr, $fn)
    {
        return array_map(function ($item) use ($fn) {
            return is_array($item) ? $this->array_map_recursive($item, $fn) : $fn($item);
        }, $arr);
    }

    /*
     * PHP array_merge_recursive creates arrays where there
     * where none when merging.
     */
    private function array_merge_recursive(array $array, array $merge): array
    {
        foreach ($merge as $key => $value) {
            if (array_key_exists($key, $array) && is_array($array[$key])) {
                $array[$key] = $this->array_merge_recursive($array[$key], $value);
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    /**
     * @param array $data
     * @param array $params
     * @return array
     */
    public function addQueries(array $data, array $params): array
    {
        $seperator = '{{ˇ෴ˇ}}';
        $queries = $this->option('queries', []);

        // add queries from options
        foreach ($queries as $query) {
            $result = explode(
                $seperator,
                str_replace('.', $seperator, $query) . $seperator . '{{' . $query . '}}'
            );
            // thanks @distantnative and @phm_van_den_Kirby
            $result = array_reduce(array_reverse($result), function ($acc, $item) {
                return $acc ? [$item => $acc] : $item;
            });
            $data = $this->array_merge_recursive($data, $result);
        }

        return $data;
    }

    public function resolveQueries(array $data, array $params): array
    {
        // resolve queries in data
        return $this->array_map_recursive($data, static function ($value) use ($params) {
            if (is_a($value, Field::class)) {
                $value = $value->value();
            }
            if (is_string($value) && Str::contains($value, '{{') && Str::contains($value, '}}')) {
                $value = Str::template($value, $params);
            }
            return $value;
        });

        return $data;
    }

    /**
     * @param array $data
     * @param Page $page
     * @return array
     */
    public function modelData(array $data, ?Page $page)
    {
        if (!$page) {
            return $data;
        }
        $hbsData = $page->handlebarsData();
        if ($hbsData && is_array($hbsData) && count($hbsData)) {
            $data = $this->array_merge_recursive($data, $hbsData);
        }
        return $data;
    }

    private static $kqlJsonFileCache;
    public function kqlData(array $data, string $template, ?Page $page = null)
    {
        if (!class_exists('Kirby\\Kql\\Kql')) {
            return $data;
        }
        $json = null;
        $jsonFile = kirby()->roots()->templates() . '/' . $template . '.' . $this->option('extension-kql');
        if (!static::$kqlJsonFileCache) {
            static::$kqlJsonFileCache = [];
        }
        if (!array_key_exists($jsonFile, static::$kqlJsonFileCache)) {
            if (!file_exists($jsonFile)) {
                return $data;
            }
            $json = Json::read($jsonFile);
            static::$kqlJsonFileCache[$jsonFile] = $json;
        } else {
            $json = static::$kqlJsonFileCache[$jsonFile];
        }

        $kqlData = \Kirby\Kql\Kql::run($json, $page);
        $kqlData = Json::decode(Json::encode($kqlData)); // flatten all objects

        if ($kqlData && is_array($kqlData) && count($kqlData)) {
            $data = $this->array_merge_recursive($data, ['page' => $kqlData]);
        }
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function prune(array $data): array
    {
        // remove kirby objects to allow json serialization for cache
        $prune = ['kirby', 'site', 'pages', 'page'];
        foreach ($prune as $key) {
            if (array_key_exists($key, $data)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function fieldsToValue(array $data): array
    {
        $data = array_map(static function ($object) {
            if ($object && is_object($object) && is_a($object, 'Kirby\Cms\Field')) {
                return $object->value();
            }
            return $object;
        }, $data);
        return $data;
    }

    /**
     * @param string $template
     * @param array $data
     * @return string|null
     */
    public function read(string $template, array $data): ?string
    {
        if (!$this->option('render')) {
            $this->renderCacheId = null;
            return null;
        }

        $this->renderCacheId = $template . '-' . crc32(json_encode($data));

        return kirby()->cache('bnomei.handlebars.render')->get($this->renderCacheId);
    }

    /**
     * @return mixed
     */
    public function renderCacheId()
    {
        return $this->renderCacheId;
    }

    /**
     * @param string|null $renderCacheId
     * @param string $result
     * @return bool
     */
    public function write(?string $renderCacheId = null, string $result): bool
    {
        if ($renderCacheId && $this->option('render')) {
            return kirby()->cache('bnomei.handlebars.render')->set($renderCacheId, $result);
        }
        return false;
    }

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function handlebars(string $template, array $data): string
    {
        // NOTE: since LightnCandy returns a Closure and
        // these can not be packed into a var as string
        // a snippet is used to echo rendering
        return snippet('handlebars/render', [
            'precompiledTemplate' => $this->lncFiles->precompiledTemplate($template),
            'data' => $data,
        ], true);
    }

    /**
     * @param $name
     * @param array $data
     * @param null $root
     * @param null $file
     * @param bool $return
     * @return string|null
     */
    public function render($name, array $data = [], $root = null, $file = null, $return = false): ?string
    {
        $template = $this->name($file ?? $name);

        $params = [
            'kirby' => A::get($data, 'kirby'),
            'site' => A::get($data, 'site'),
            'page' => A::get($data, 'page'),
        ];

        $data = $this->prune($data);
        $data = $this->addQueries($data, $params);
        $data = $this->modelData($data, $params['page']);
        $data = $this->kqlData($data, $template, $params['page']);
        $data = $this->resolveQueries($data, $params);
        $data = $this->fieldsToValue($data);

        $result = $this->read($template, $data);

        if (!$result) {
            $result = $this->handlebars($template, $data);
        }

        $this->write($this->renderCacheId, $result);

        if (!$return) {
            echo $result;
            return null;
        }
        return $result;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function flush()
    {
        try {
            kirby()->cache('bnomei.handlebars.render')->flush();
            $this->lncFiles->flush();
        } catch (Exception $e) {
            //
        }
    }
}
