<?php

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        // SaleBundle
        'Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider' => 'Oro\Bundle\SaleBundle\Provider\QuoteProductPricesProvider',
        'Oro\Bundle\SaleBundle\Quote\Pricing\QuotePriceComparator' => 'Oro\Bundle\SaleBundle\Quote\Pricing\QuotePricesComparator',
        // RFPBundle
        'Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider' => 'Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider',
        // ShoppingListBundle
        'Oro\Bundle\CheckoutBundle\EventListener\DatagridLineItemsDataViolationsListener' => 'Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationListener',
        // CheckoutBundle
        'Oro\Bundle\CheckoutBundle\DataProvider\LineItem\CheckoutLineItemsDataProvider' => 'Oro\Bundle\CheckoutBundle\DataProvider\CheckoutDataProvider',
        'Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalProvider' => 'Oro\Bundle\CheckoutBundle\Provider\SubtotalProvider',
        // PaymentBundle
        'Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface' => 'Oro\Bundle\PaymentBundle\Context\PaymentLineItem',
        'Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection' => 'Doctrine\Common\Collections\Collection',
        // ShippingBundle
        'Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface' => 'Oro\Bundle\ShippingBundle\Context\ShippingLineItem',
        'Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface' => 'Doctrine\Common\Collections\Collection',
        'Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection' => 'Doctrine\Common\Collections\Collection',
        // ShoppingListBundle
        'Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataViolationsListener' => 'Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationListener',
        'Oro\Bundle\ShoppingListBundle\ProductKit\Checksum\LineItemChecksumGeneratorInterface' => 'Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface',
        'Oro\Bundle\ShoppinglistBundle\ProductKit\Checker\ProductKitAvailabilityChecker' => 'Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitAvailabilityChecker',
        'Oro\Bundle\ShoppinglistBundle\ProductKit\Checker\ProductKitItemAvailabilityChecker' => 'Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitItemAvailabilityChecker',
        'Oro\Bundle\ShoppinglistBundle\ProductKit\Checker\ProductKitItemProductAvailabilityChecker' => 'Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitItemProductAvailabilityChecker',
        'Oro\Bundle\ShoppinglistBundle\ProductKit\Provider\ProductKitItemsProvider' => 'Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemsProvider',
        'Oro\Bundle\ShoppinglistBundle\ProductKit\Provider\ProductKitItemProductsProvider' => 'Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemProductsProvider',
        // ProductBundle
        'Oro\Bundle\ProductBundle\ProductKit\EventListener\ProductStatusListener' => 'Oro\Bundle\ProductBundle\ProductKit\EventListener\StatusListener',
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        // InventoryBundle
        new MethodCallRename(
            'Oro\Bundle\InventoryBundle\Validator\LowInventoryCheckoutLineItemValidator',
            'isLineItemRunningLow',
            'isRunningLow'
        ),
        new MethodCallRename(
            'Oro\Bundle\InventoryBundle\Validator\LowInventoryCheckoutLineItemValidator',
            'getMessageIfLineItemRunningLow',
            'getMessageIfRunningLow'
        ),
        // CMSBundle
        new MethodCallRename(
            'Oro\Bundle\CMSBundle\Entity\ImageSlide',
            'getTitle',
            'getAltImageText'
        ),
        new MethodCallRename(
            'Oro\Bundle\CMSBundle\Entity\ImageSlide',
            'setTitle',
            'setAltImageText'
        ),
        new MethodCallRename(
            'Oro\Bundle\CMSBundle\Entity\ImageSlide',
            'getMainImage',
            'getExtraLargeImage'
        ),
        new MethodCallRename(
            'Oro\Bundle\CMSBundle\Entity\ImageSlide',
            'setMainImage',
            'setExtraLargeImage'
        ),
    ]);
};
