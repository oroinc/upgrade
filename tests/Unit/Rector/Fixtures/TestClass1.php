<?php

namespace Oro\UpgradeToolkit\Tests\Unit\Rector\Fixtures;

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
