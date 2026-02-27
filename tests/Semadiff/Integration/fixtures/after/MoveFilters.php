<?php

declare(strict_types=1);

namespace OroLab\Bundle\DataGridCustomizationBundle\Customization;

use OroLab\Bundle\DataGridCustomizationBundle\Customization\DTO\CustomizationContext;

class MoveFilters implements DatagridCustomizationInterface
{
    #[\Override]
    public function customize(CustomizationContext $context): void
    {
        $config = $context->getConfig();
        $filters = $config->offsetGetByPath('[filters][columns]', []);
        if (empty($filters)) {
            return;
        }

        foreach ($filters as $key => $filter) {
            $config->offsetSetByPath(sprintf('[filters][columns][%s][order]', $key), $filter['order'] ?? 0);
        }
    }

    #[\Override]
    public function isApplicable(CustomizationContext $context): bool
    {
        return !empty($context->getConfig()->offsetGetByPath('[filters][columns]', []));
    }
}
