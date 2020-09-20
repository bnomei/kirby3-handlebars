<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Dir;
use Kirby\Toolkit\F;
use LightnCandy\LightnCandy;

final class LncFiles
{
    /**
     * @var array
     */
    private $files;

    /**
     * @var string
     */
    private $modified;

    /**
     * LncFiles constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'debug' => option('debug'),
            'compile-flags' => option('bnomei.handlebars.compile-flags'),
            'extension-input' => option('bnomei.handlebars.extension-input'),
            'extension-output' => option('bnomei.handlebars.extension-output'),
            'files' => option('bnomei.handlebars.files'),
            'lnc' => option('bnomei.handlebars.lnc'),
            'dir-templates' => option('bnomei.handlebars.dir-templates'),
            'dir-partials' => option('bnomei.handlebars.dir-partials'),
        ];
        $this->options = array_merge($defaults, $options);

        $this->options['files'] = $this->options['files'] && !$this->options['debug'];
        $this->options['lnc'] = $this->options['lnc'] && !$this->options['debug'];

        foreach ($this->options as $key => $call) {
            if (!is_string($call) && is_callable($call) && !in_array($call, ['hbs', 'handlebars'])) {
                $this->options[$key] = $call();
            }
        }

        if ($this->option('debug')) {
            $this->flush();
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
     * @return array
     */
    public function compileOptions()
    {
        return [
            'flags' => $this->option('compile-flags'),
            'partialresolver' => function ($context, $name) {
                return self::singleton()->hbsOfPartial($name);
            },
        ];
    }

    /**
     * @param string $name
     * @return string
     */
    public function hbsOfPartial(string $name): string
    {
        foreach ($this->files as $lncFile) {
            if ($lncFile->partial() && $lncFile->name() === $name) {
                return $lncFile->hbs();
            }
        }
        return '';
    }

    /**
     * @param string $dir
     * @param string $extension
     * @return array
     */
    public function filterDirByExtension(string $dir, string $extension)
    {
        $result = [];
        foreach (Dir::files($dir, null, true) as $file) {
            if (F::extension($file) === $extension) {
                $result[] = $file;
            }
        }
        return $result;
    }

    /**
     * @param array $files
     * @return string
     */
    public function modified(array $files = []): string
    {
        if ($this->modified) {
            return $this->modified;
        }
        if (count($files) === 0) {
            $files = array_merge(
                $this->filterDirByExtension(
                    (string)$this->option('dir-templates'),
                    (string)$this->option('extension-input')
                ),
                $this->filterDirByExtension(
                    (string)$this->option('dir-partials'),
                    (string)$this->option('extension-input')
                )
            );
        }

        $modified = ['LncFilesSalt'];
        foreach ($files as $file) {
            $modified[] = F::modified($file);
        }

        $this->modified = strval(crc32(implode($modified)));
        return $this->modified;
    }

    /**
     * @return array
     */
    public function scan(): array
    {
        $files = [];
        $dirs = [
            [$this->option('dir-templates'), false],
            [$this->option('dir-partials'), true],
        ];

        foreach ($dirs as $dir) {
            $templates = $this->filterDirByExtension(
                (string)$dir[0],
                (string)$this->option('extension-input')
            );
            // first get all
            foreach ($templates as $file) {
                $name = basename($file, '.' . $this->option('extension-input'));
                // ignore all files starting with _ (like fractals.build _preview.hbs)
                if (substr($name, 0, 1) === '_') {
                    continue;
                }
                $files[] = new LncFile([
                    'name' => $name,
                    'source' => $file,
                    'target' => $this->target($file, $dir[1]),
                    'partial' => $dir[1],
                    'modified' => F::modified($file),
                    'lnc' => $this->option('lnc'),
                ]);
            }
        }

        return $files;
    }

    /**
     * @param LncFile $lncFile
     * @return false|string
     */
    public function compile(LncFile $lncFile)
    {
        return LightnCandy::compile(
            $lncFile->hbs(),
            $this->compileOptions()
        );
    }

    /**
     * @param string $file
     * @param bool $partial
     * @return string
     */
    public function target(string $file, bool $partial = false)
    {
        $path = [
            $this->lncCacheRoot(),
            DIRECTORY_SEPARATOR,
            ($partial ? '@' : ''),
            basename($file, '.' . $this->option('extension-input')),
            '.' . $this->option('extension-output'),
        ];
        return implode($path);
    }

    /**
     * @return array
     */
    public function load()
    {
        $files = [];

        if ($this->option('files')) {
            $files = kirby()->cache('bnomei.handlebars.files')->get(
                $this->modified(),
                []
            );
            $files = array_map(function ($file) {
                return new LncFile($file);
            }, $files);
        }
        if (count($files)) {
            return $files;
        }

        return $this->scan();
    }

    /**
     * @param array $files
     * @return bool
     */
    public function write(array $files): bool
    {
        if (!$this->option('files')) {
            return false;
        }
        return kirby()->cache('bnomei.handlebars.files')->set(
            $this->modified(),
            array_map(function ($file) {
                return $file->toArray();
            }, $files)
        );
    }

    /**
     * @return array
     */
    public function registerAllTemplates(): array
    {
        $this->files = $this->load();

        $anyPartialNeedsUpdate = false;
        foreach ($this->files as $lncFile) {
            if ($lncFile->partial() && $lncFile->needsUpdate()) {
                $anyPartialNeedsUpdate = true;
                $lncFile->writePartial();
            }
        }

        foreach ($this->files as $lncFile) {
            if (!$lncFile->partial() && ($anyPartialNeedsUpdate || $lncFile->needsUpdate())) {
                $lncFile->php($this->compile($lncFile));
            }
        }

        $this->write($this->files);
        return $this->files;
    }

    /**
     * @param string $name
     * @return string
     * @throws InvalidArgumentException
     */
    public function lncFile(string $name): string
    {
        foreach ($this->files as $lncFile) {
            if ($lncFile->name() === $name) {
                return $lncFile->target();
            }
        }
        if($name === 'default') {
            throw new InvalidArgumentException(); // @codeCoverageIgnore
        }
        return $this->lncFile('default');
    }

    /**
     * @param string $name
     * @return string
     * @throws InvalidArgumentException
     */
    public function hbsFile(string $name): string
    {
        foreach ($this->files as $lncFile) {
            if ($lncFile->name() === $name) {
                return F::realpath($lncFile->source());
            }
        }
        if($name === 'default') {
            throw new InvalidArgumentException(); // @codeCoverageIgnore
        }
        return $this->hbsFile('default');
    }

    /**
     * @param $name
     * @return string
     */
    public function precompiledTemplate($name): string
    {
        foreach ($this->files as $lncFile) {
            if ($lncFile->partial() === false && $lncFile->name() === $name) {
                $php = $lncFile->php();
                if (! $php) {
                    $php = $this->compile($lncFile);
                }
                return $php;
            }
        }
        return $this->precompiledTemplate('default');
    }

    /**
     * @return string
     */
    public function lncCacheRoot(): string
    {
        // TODO: https://github.com/getkirby/ideas/issues/390
        $path = implode([
            kirby()->roots()->cache(),
            DIRECTORY_SEPARATOR,
            'plugins',
            DIRECTORY_SEPARATOR,
            'bnomei',
            DIRECTORY_SEPARATOR,
            'handlebars',
            DIRECTORY_SEPARATOR,
            $this->option('extension-output'),
        ]);
        Dir::make($path);

        return $path;
    }

    /**
     *
     */
    public function flush()
    {
        kirby()->cache('bnomei.handlebars.files')->flush();

        foreach(Dir::read($this->lncCacheRoot()) as $file) {
            $file = $this->lncCacheRoot() . '/' . $file;
            if(is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * @return mixed
     */
    public function files()
    {
        return $this->files;
    }

    /*
     * @var LncFiles
     */
    private static $singleton;

    /**
     * @param array $options
     * @return LncFiles
     * @codeCoverageIgnore
     */
    public static function singleton(array $options = [])
    {
        if (!self::$singleton) {
            self::$singleton = new self($options);
            self::$singleton->registerAllTemplates();
        }

        return self::$singleton;
    }
}
