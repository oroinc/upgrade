<?php

use Rector\Config\RectorConfig;
use Rector\Removing\Rector\Class_\RemoveTraitUseRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;

return static function (RectorConfig $rectorConfig): void {
    // v4.1.0

    // The trait Oro\Component\Cache\Layout\DataProviderCacheTrait was removed
    // as it added additional complexity to cacheable layout data providers instead of simplify them.
    $rectorConfig->ruleWithConfiguration(RemoveTraitUseRector::class, [
        'Oro\Component\Cache\Layout\DataProviderCacheTrait'
    ]);

    // v4.2.0

    // The method
    // Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository::findByProductsAndUnits()
    // was renamed to
    // Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository::findIndexedByProductsAndUnits()
    // and now uses a plain DQL query without entity hydration.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository',
            'findByProductsAndUnits',
            'findIndexedByProductsAndUnits'
        )
    ]);

    // Method
    // Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory::createDecoratedProductByProductHolders()
    // is removed, use
    // Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory::createDecoratedProduct()
    // instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory',
            'createDecoratedProductByProductHolders',
            'createDecoratedProduct'
        )
    ]);

    // Method
    // Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::findDuplicate()
    // is removed, use
    // Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::findDuplicateInShoppingList()
    // instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository',
            'findDuplicate',
            'findDuplicateInShoppingList'
        )
    ]);

    // Methods
    // Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::deleteItemsByShoppingListAndInventoryStatuses(),
    // Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::deleteDisabledItemsByShoppingList()
    // are removed, use
    // Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::deleteNotAllowedLineItemsFromShoppingList()
    // instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository',
            'deleteItemsByShoppingListAndInventoryStatuses',
            'deleteNotAllowedLineItemsFromShoppingList'
        ),
        new MethodCallRename(
            'Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository',
            'deleteDisabledItemsByShoppingList',
            'deleteNotAllowedLineItemsFromShoppingList'
        ),
    ]);
};
