<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\A;

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
            'cache.render' => option('bnomei.handlebars.cache.render'),
        ];
        $this->options = array_merge($defaults, $options);
        $this->options['cache.render'] = $this->options['cache.render'] && !$this->options['debug'];

        foreach ($this->options as $key => $call) {
            if (is_callable($call)) {
                $this->options[$key] = $call();
            }
        }

        $this->lncFiles = LncFiles::singleton($this->options);

        if ($this->option('debug')) {
            kirby()->cache('bnomei.handlebars.render')->flush();
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
    public function file($name): string {
        return $this->lncFiles->hbsFile($name);
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
     * @param string $template
     * @param array $data
     * @return string|null
     */
    public function read(string $template, array $data): ?string
    {
        if (!$this->option('cache.render')) {
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
        if ($renderCacheId && $this->option('cache.render')) {
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
        $data = $this->prune($data);

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
        kirby()->cache('bnomei.handlebars.render')->flush();
        $this->lncFiles->flush();
    }


}
