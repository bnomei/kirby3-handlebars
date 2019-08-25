<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Cms\Template;

final class HandlebarsTemplate extends Template
{
    /*
     * @var \Bnomei\Handlebars
     */
    private $handlebars;

    /**
     * HandlebarsTemplate constructor.
     * @param string $name
     * @param string $type
     * @param string $defaultType
     */
    public function __construct(string $name, string $type = 'html', string $defaultType = 'html')
    {
        $this->handlebars = new Handlebars();

        parent::__construct($name, $type, $defaultType);
    }

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function extension(): string
    {
        return option('bnomei.handlebars.extension-input');
    }

    /**
     * @return string
     */
    public function file(): string
    {
        $file = $this->handlebars->file(
            $this->name()
        );
        return $file;
    }

    /**
     * @param array $data
     * @return string
     */
    public function render(array $data = []): string
    {
        $render = $this->handlebars->render(
            $this->name(),
            $data,
            $this->root(),
            $this->file(),
            true
        );
        return strval($render);
    }
}
