<?php

declare(strict_types=1);

namespace DT\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The main form type to manage {@link CustomerTier}.
 */
class CustomerTierType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'dt.entity.customertier.name.label',
                ],
            )
            ->add(
                'days',
                IntegerType::class,
                [
                    'required' => true,
                    'label' => 'dt.entity.customertier.days.label',
                ],
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => CustomerTier::class,
            ],
        );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'dt_customer_tier';
    }
}
