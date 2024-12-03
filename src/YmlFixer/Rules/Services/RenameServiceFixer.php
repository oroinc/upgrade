<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Renames services in the service configs
 */
class RenameServiceFixer implements YmlFixerInterface
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

        $services = $config[Keys::SERVICES];
        $configuration = $this->config();
        $serviceRenameCallback = function ($value) use ($configuration) {
            foreach ($configuration as $old => $new) {
                $value = $old === $value ? $new : $value;
                $value = '@' . $old === $value ? '@' . $new : $value;
            }

            return $value;
        };
        $this->rename($services, $serviceRenameCallback);
        $config[Keys::SERVICES] = $services;
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

    private function rename(array &$array, callable $callback): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->rename($value, $callback);
            } elseif (is_string($value)) {
                $value = $callback($value);
            }
        }
    }

    private function config(): array
    {
        // old_service.name => new_service.name
        return $this->ruleConfiguration ?? [
            'oro_attachment.manager.media_cache_manager_registry' => 'oro_attachment.media_cache_manager_registry',
            'oro_attachment.provider.attachment_file_name_provider' => 'oro_attachment.provider.file_name',
            'oro_entity.virtual_field_provider.chain' => 'oro_entity.virtual_field_provider',
            'oro_entity.virtual_relation_provider.chain' => 'oro_entity.virtual_relation_provider',
            'oro_query_designer.query_designer.filter_processor' => 'oro_segment.query.filter_processor',
            'oro_sso.oauth_provider' => 'oro_sso.oauth_user_provider',
            'oro.cache.abstract' => 'oro.data.cache',
            'oro_entity_merge.accessor.delegate' => 'oro_entity_merge.accessor',
            'oro_entity_merge.strategy.delegate' => 'oro_entity_merge.strategy',
            'oro_notification.manager.email_notification_sender' => 'oro_notification.manager.email_notification',
        ];
    }
}
