<?php

namespace Oro\Tests\Unit\Fixtures;

class TestClass1
{
    protected ?int $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}
