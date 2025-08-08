<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro61\Enum\ReplaceExtendExtensionAwareTraitRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class ReplaceExtendExtensionAwareTraitRectorTest extends AbstractRectorTestCase
{
    public function testReplacesTraitAndMembers(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/replace_trait_and_members.php.inc');
    }

    public function testNoChangeIfTraitNotPresent(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/no_trait_present.php.inc');
    }

    public function testNoChangeIfNotMigration(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/not_migration.php.inc');
    }

    public function testHandlesOutdatedExtendExtensionEdgeCase(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/outdated_extend_extension_edge_case.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
