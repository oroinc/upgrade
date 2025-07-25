<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Routing;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Updates routing type from annotation to attribute
 */
class RoutingTypeFixer implements YmlFixerInterface
{
    private const ANNOTATION = 'annotation';
    private const ATTRIBUTE = 'attribute';

    #[\Override]
    public function fix(array &$config): void
    {
        foreach ($config as $route => $routeConfig) {
            if (array_key_exists(Keys::TYPE, $routeConfig) && self::ANNOTATION === $routeConfig[Keys::TYPE]) {
                $config[$route][Keys::TYPE] = self::ATTRIBUTE;
            }
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/routing.yml';
    }
}
