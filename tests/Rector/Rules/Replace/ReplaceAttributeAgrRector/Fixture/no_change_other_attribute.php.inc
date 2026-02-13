<?php

namespace App;

#[\Attribute]
final class OtherAttribute
{
    public function __construct(public string $name = '')
    {
    }
}

final class NoChangeOtherAttribute
{
    #[OtherAttribute(name: 'old')]
    public function action(): void
    {
    }
}
