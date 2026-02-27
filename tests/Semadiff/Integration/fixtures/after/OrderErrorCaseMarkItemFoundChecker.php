<?php

declare(strict_types=1);

namespace DT\Bundle\CustomerServiceBundle\Checker;

use DT\Bundle\EntityBundle\Entity\OrderErrorCase;
use DT\Bundle\EntityBundle\Entity\Repository\OrderErrorCaseRepository;
use DT\Bundle\SetupBundle\Model\EnumValues;

class OrderErrorCaseMarkItemFoundChecker
{
    public function __construct(private readonly OrderErrorCaseRepository $caseRepository)
    {
    }

    public function isApplicable(?OrderErrorCase $orderErrorCase): bool
    {
        if (null === $orderErrorCase?->getId()) {
            return false;
        }

        $isNewOrSubmitStatus = $orderErrorCase->getStatus() &&
            \in_array(
                $orderErrorCase->getStatus()->getInternalId(),
                [
                    EnumValues::DT_ORDER_ERROR_CASE_STATUS_NEW,
                    EnumValues::DT_ORDER_ERROR_CASE_STATUS_SUBMITTED,
                ],
                true,
            );

        if (!$isNewOrSubmitStatus || $orderErrorCase->getIsItemFound()) {
            return false;
        }

        return $this->caseRepository->hasItemWithQtyGreaterZero($orderErrorCase->getId());
    }
}
