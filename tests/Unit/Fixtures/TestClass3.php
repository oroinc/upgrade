<?php

namespace Oro\UpgradeToolkit\Tests\Unit\Fixtures;

final class TestClass3
{
    protected string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
