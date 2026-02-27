<?php

declare(strict_types=1);

namespace DT\Bundle\QuotesBundle\Feature\OpportunityMatch\Provider;

class DTQuoteOpportunityGridParamsProvider
{
    public function __construct(
        private readonly OpportunityRepository $repository,
        private readonly TokenAccessorInterface $tokenAccessor,
    ) {
    }

    public function getParams(DTQuote $quote): array
    {
        $opportunity = $quote->getOpportunity();
        if (null === $opportunity) {
            return [];
        }

        return [
            'opportunity_id' => $opportunity->getInternalId(),
            'customer_id' => $quote->getCustomer()?->getId(),
            'user_id' => $this->tokenAccessor->getUserId(),
        ];
    }

    public function getGridName(): string
    {
        return 'dt-quote-opportunity-grid';
    }
}
