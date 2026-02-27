<?php

declare(strict_types=1);

namespace DT\Bundle\CustomerBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerSelectTypeExtension extends AbstractTypeExtension
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete_alias' => 'dt_customer_select',
            'configs' => [
                'placeholder' => 'oro.customer.customer.form.choose',
            ],
        ]);
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OroEntitySelectOrCreateInlineType::class];
    }
}
