<?php

declare(strict_types=1);

namespace DT\Bundle\OrderBundle\EventListener\Form;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class FormViewListenerDecorator
{
    public function __construct(
        private readonly FormViewListener $innerListener,
        private readonly DoctrineHelper $doctrineHelper,
    ) {
    }

    public function onView(ViewEvent $event): void
    {
        $entity = $this->doctrineHelper->getEntityReference(
            CustomerUser::class,
            $event->getEntityId(),
        );

        if (null === $entity) {
            return;
        }

        $this->innerListener->onView($event);
    }

    public function getEntityClass(): string
    {
        return CustomerUser::class;
    }
}
