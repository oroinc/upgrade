<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Replace setNamespace calls to the tags config for oro.data.cache child services
 */
class ServicesNamespaceCallsToCachePoolTagsFixer implements YmlFixerInterface
{
    private const ORO_DATA_CACHE = 'oro.data.cache';
    private const TAG_NAME = 'cache.pool';
    private const SET_NAMESPACE_CALL = 'setNamespace';

    #[\Override]
    public function fix(array &$config): void
    {
        if (!$this->isProcessable($config)) {
            return;
        }

        foreach ($config[Keys::SERVICES] as $serviceName => $def) {
            if (!is_array($def) || !array_key_exists(Keys::PARENT, $def)) {
                continue;
            }

            if (self::ORO_DATA_CACHE !== $def[Keys::PARENT]) {
                continue;
            }

            if (array_key_exists(Keys::CALLS, $def)) {
                $namespace = $this->getNamespace($def[Keys::CALLS]);

                if ($namespace) {
                    if (count($def[Keys::CALLS]) <= 1) {
                        unset($config[Keys::SERVICES][$serviceName][Keys::CALLS]);
                    }

                    $tag = [
                        Keys::NAME => self::TAG_NAME,
                        Keys::NAMESPACE => $namespace,
                    ];
                    $config[Keys::SERVICES][$serviceName][Keys::TAGS][] = $tag;
                }
            }
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/**.yml';
    }

    private function isProcessable(array $config): bool
    {
        return array_key_exists(Keys::SERVICES, $config) && is_array($config[Keys::SERVICES]);
    }

    private function getNamespace(array $calls): ?string
    {
        $namespace = null;
        foreach ($calls as $call) {
            if (self::SET_NAMESPACE_CALL === $call[0]) {
                $namespace = $call[1][0];
            }
        }

        return $namespace;
    }
}
