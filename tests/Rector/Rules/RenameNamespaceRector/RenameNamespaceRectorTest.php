<?php

namespace Rector\Rules\RenameNamespaceRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class RenameNamespaceRectorTest extends AbstractRectorTestCase
{
    public function test(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/fixture.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
