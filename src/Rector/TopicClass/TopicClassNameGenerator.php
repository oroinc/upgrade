<?php

declare(strict_types=1);

namespace Oro\Rector\TopicClass;

class TopicClassNameGenerator
{
    private static function camelCase(string $input): string
    {
        $output = \lcfirst(\str_replace(' ', '', \ucwords(\str_replace('_', ' ', $input))));

        return \preg_replace('#\\W#', '', $output);
    }

    public static function getByConstantName(string $name): string
    {
        return \ucfirst(self::camelCase(\strtolower($name) . 'Topic'));
    }
}
