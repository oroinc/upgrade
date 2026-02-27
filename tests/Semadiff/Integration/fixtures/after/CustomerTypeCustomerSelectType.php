<?php

declare(strict_types=1);

namespace DT\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows selecting only Bill-To/Ship-To Customer (ignores Groups, Rollups, and Subs).
 */
class CustomerTypeCustomerSelectType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'dt_customer_type_customer',
                'create_form_route' => 'oro_customer_customer_create',
                'configs' => [
                    'placeholder' => 'oro.customer.customer.form.choose',
                    'result_template_twig' => '@DTCustomer/Customer/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@DTCustomer/Customer/Autocomplete/selection.html.twig',
                ],
                'attr' => [
                    'class' => 'customer-type-customer-select',
                ],
                'grid_name' => 'dt-customer-type-customer-select-grid',
            ],
        );
    }

    #[\Override]
    public function getParent(): string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }
}
