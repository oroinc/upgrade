<?php

declare(strict_types=1);

namespace DT\Bundle\CustomerServiceBundle\Checker;

use DT\Bundle\EntityBundle\Entity\OrderErrorCase;
use DT\Bundle\EntityBundle\Entity\Repository\OrderErrorCaseRepository;
use DT\Bundle\SetupBundle\Model\EnumValues;

class OrderErrorCaseMarkItemFoundChecker
{
    /** @var OrderErrorCaseRepository */
    protected $caseRepository;

    /**
     * @param OrderErrorCaseRepository $caseRepository
     */
    public function __construct(OrderErrorCaseRepository $caseRepository)
    {
        $this->caseRepository = $caseRepository;
    }

    /**
     * @param OrderErrorCase|null $orderErrorCase
     * @return bool
     */
    public function isApplicable(?OrderErrorCase $orderErrorCase): bool
    {
        if (null === $orderErrorCase || null === $orderErrorCase->getId()) {
            return false;
        }

        $isNewOrSubmitStatus = $orderErrorCase->getStatus() &&
            \in_array(
                $orderErrorCase->getStatus()->getId(),
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
