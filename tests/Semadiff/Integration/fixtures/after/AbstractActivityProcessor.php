<?php

declare(strict_types=1);

namespace DT\Bundle\SalesforceImportBundle\Processor\Convert\Activity;

class AbstractActivityProcessor
{
    protected function getResult(ContextInterface $context): array
    {
        $result = $context->get(ContextConstants::RESULT) ?: [];
        return $result;
    }

    protected function processItem($item): void
    {
        $this->handler->handle($item);
    }
}
