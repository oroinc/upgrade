<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Changes the sign of the priority value for provided service tags
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class ServiceTagsPriorityFixer implements YmlFixerInterface
{
    public function __construct(
        private ?array $ruleConfiguration = null,
    ) {
    }

    #[\Override]
    public function fix(array &$config): void
    {
        if (!$this->isProcessable($config)) {
            return;
        }

        foreach ($config[Keys::SERVICES] as $serviceName => $serviceDef) {
            if (is_array($serviceDef)
                && array_key_exists(Keys::TAGS, $serviceDef)
                && is_array($serviceDef[Keys::TAGS])
            ) {
                foreach ($serviceDef[Keys::TAGS] as $tagKey => $tagDef) {
                    if (is_array($tagDef)
                        && array_key_exists(Keys::PRIORITY, $tagDef)
                        && in_array($tagDef[Keys::NAME], $this->config())
                    ) {
                        $priority = $tagDef[Keys::PRIORITY];
                        $priority = (is_int($priority) && $priority > 0) ? $priority - ($priority * 2) : $priority;
                        $config[Keys::SERVICES][$serviceName][Keys::TAGS][$tagKey][Keys::PRIORITY] = $priority;
                    }
                }
            }
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/*.yml';
    }

    private function isProcessable(array $config): bool
    {
        return array_key_exists(Keys::SERVICES, $config) && is_array($config[Keys::SERVICES]);
    }

    private function config(): array
    {
        return $this->ruleConfiguration ?? [
            'oro_config.configuration_search_provider',
            'oro_datagrid.extension.action.provider',
            'oro_translation.extension.translation_context_resolver',
            'oro_translation.extension.translation_strategy',
            'oro.workflow.configuration.handler',
            'oro.workflow.definition_builder.extension',
            'oro_platform.console.global_options_provider',
            'oro.security.filter.acl_privilege',
            'oro_promotion.promotion_context_converter',
            'oro_promotion.discount_strategy',
        ];
    }
}
