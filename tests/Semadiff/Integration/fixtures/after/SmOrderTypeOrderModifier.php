<?php

declare(strict_types=1);

namespace DT\Bundle\OrderBundle\Feature\OrderModifier\Modifier\Order;

class SmOrderTypeOrderModifier implements OrderModifierInterface
{
    public function __construct(
        private readonly ConfigManager $configManager,
    ) {
    }

    public function modify(Order $order, ShoppingList $shoppingList): void
    {
        $orderType = $this->configManager->get('dt_order.sm_order_type');

        if (null === $orderType) {
            return;
        }

        $type = $order->getType();
        if (null !== $type && EnumHelper::getSafeInternalId($type) === $orderType) {
            return;
        }

        $order->setInternalOrderType($orderType);
    }

    public function isApplicable(Order $order): bool
    {
        return null !== $order->getShoppingList();
    }
}
