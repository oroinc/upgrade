<?php

namespace Foo\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SomeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaults = ['input' => 'array'];
        $resolver->setDefaults($defaults);
    }
}
