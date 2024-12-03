<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Rules\Services\RenameSessionServiceArgumentFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class RenameSessionServiceArgumentFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            RenameSessionServiceArgumentFixer::class,
            self::FIXTURES_PATH . '/services.yml',
            self::FIXTURES_PATH . '/ExpectedResults/rename_session_argument_services.yml'
        );
    }
}
