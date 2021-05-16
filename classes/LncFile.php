<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Toolkit\A;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Str;

final class LncFile
{
    /*
     * @var array
     */
    private $data;

    /**
     * LncFile constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $this->read($data);
    }

    /**
     * @return string|null
     */
    public function source(): ?string
    {
        return A::get($this->data, 'source');
    }

    /**
     * @return string|null
     */
    public function name(): ?string
    {
        return A::get($this->data, 'name');
    }

    /**
     * @return string|null
     */
    public function target(): ?string
    {
        return A::get($this->data, 'target');
    }

    /**
     * @return bool
     */
    public function partial(): bool
    {
        return A::get($this->data, 'partial', false);
    }

    /**
     * @return bool
     */
    public function needsUpdate(): bool
    {
        return A::get($this->data, 'needsUpdate', false);
    }

    /**
     * @return int|null
     */
    public function modified(): ?int
    {
        return A::get($this->data, 'modified');
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $copy = $this->data;

        foreach (['hbs', 'php'] as $remove) {
            if (A::get($copy, $remove)) {
                unset($copy[$remove]);
            }
        }
        return $copy;
    }

    /**
     * @param array $data
     */
    public function read(array $data)
    {
        $source = $data['source'];
        $target = A::get($data, 'target');

        $data['needsUpdate'] = false;
        if ($target && F::exists($target) === false) {
            $data['needsUpdate'] = true;
        } elseif ($source && $target && F::exists($target) && F::modified($source) > F::modified($target)) {
            $data['needsUpdate'] = true;
        } elseif ($source && F::modified($source) !== $data['modified']) {
            $data['needsUpdate'] = true;
        }

        return $data;
    }

    public function writePartial()
    {
        if ($this->partial()) {
            $this->data['needsUpdate'] = false;
            F::write($this->target(), ''); // touch
        }
    }

    /**
     * @param string|null $php
     * @return string|null
     */
    public function php(string $php = null): ?string
    {
        // lazy loading
        if ($php === null) {
            if ($this->target() && A::get($this->data, 'lnc') && F::exists($this->target())) {
                $php = F::read($this->target());
                $this->data['php'] = $php;
                $this->data['needsUpdate'] = false;
                return $php;
            }
        }

        // set
        if ($php) {
            $this->data['php'] = $php;

            // write
            if ($this->target() && A::get($this->data, 'lnc')) {
                $didWrite = false;
                while ($didWrite === false) {
                    try {
                        F::write($this->target(), $php);
                    } catch (\Exception $ex) {
                        //
                    } finally {
                        // validate to be 100% sure
                        $didWrite = F::read($this->target()) === $php;
                    }
                }
                $this->data['needsUpdate'] = false;
            }
        }

        return A::get($this->data, 'php');
    }

    /**
     * @return string|null
     */
    public function hbs(): ?string
    {
        $hbs = A::get($this->data, 'hbs');

        // lazy loading
        if (! $hbs) {
            if ($this->source() && F::exists($this->source())) {
                // fix fractal.build syntax
                $hbs = F::read($this->source());
                $hbs = Str::replace($hbs, '{{> @', '{{> ');
                $hbs = Str::replace($hbs, ' this }}', '}}');
                $this->data['hbs'] = $hbs;
            }
        }

        return A::get($this->data, 'hbs');
    }
}
