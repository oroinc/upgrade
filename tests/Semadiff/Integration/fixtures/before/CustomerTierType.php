<?php

declare(strict_types=1);

namespace DT\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerTierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CustomerTier::class,
            ],
        );
    }

    public function getBlockPrefix(): string
    {
        return 'dt_customer_tier';
    }
}
