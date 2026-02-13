<?php

namespace App\Mocks;

class TestClassExplicit
{
    public function __construct(
        public mixed $name = null,
        public mixed $value = null,
        public mixed $description = null
    ) {
    }
}
