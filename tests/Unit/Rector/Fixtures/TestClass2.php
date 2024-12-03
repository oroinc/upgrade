<?php

namespace Oro\UpgradeToolkit\Tests\Unit\Rector\Fixtures;

class TestClass2
{
    protected string $uuid = 'test-uuid';
    private int $value;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }
}
