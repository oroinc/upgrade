<?php

namespace Foo\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class SomeType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['input' => 'array']);
    }
}
