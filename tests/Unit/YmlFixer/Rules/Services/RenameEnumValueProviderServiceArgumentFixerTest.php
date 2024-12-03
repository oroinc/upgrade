<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Rules\Services\RenameEnumValueProviderServiceArgumentFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class RenameEnumValueProviderServiceArgumentFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            RenameEnumValueProviderServiceArgumentFixer::class,
            self::FIXTURES_PATH . '/services.yml',
            self::FIXTURES_PATH . '/ExpectedResults/enum_value_provider_services.yml'
        );
    }
}
