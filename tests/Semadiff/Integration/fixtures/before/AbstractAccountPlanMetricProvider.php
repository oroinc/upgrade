<?php

declare(strict_types=1);

namespace DT\Bundle\AccountPlanBundle\Provider\Metric\GoAccountPlan;

abstract class AbstractAccountPlanMetricProvider extends AbstractMetricValueProvider
{
    /**
     * {@inheritdoc}
     */
    public function supportsEntityClass(string $entityClass): bool
    {
        return $entityClass === GoAccountPlan::class;
    }
}
