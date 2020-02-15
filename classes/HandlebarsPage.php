<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Cms\Field;
use Kirby\Cms\Page;

class HandlebarsPage extends Page implements HandlebarsData
{
    /** @var array */
    public static $handlebarsData = [];

    public function handlebarsData(): array
    {
        if (! is_array(static::$handlebarsData)) {
            return [];
        }

        $data = array_flip(array_map(static function($value) {
            if (is_callable($value)) {
                $value = $value();
            }
            return $value ? strval($value) : null;
        }, static::$handlebarsData));

        foreach(array_keys($data) as $methodName) {
            $field = $this->{$methodName}();
            if (is_a($field, Field::class)) {
                $field = $field->value();
            }
            $data[$methodName] = $field;
        }
        return $data;
    }
}
