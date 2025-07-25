<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Rules\Services\RenameClassFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class RenameClassFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $ruleConfiguration = [
            'Oro\\UpgradeToolkit\\Tests\\SomeTestServiceClass'
            => 'Oro\\UpgradeToolkit\\Tests\\NEW\\SomeTestServiceClass'
        ];
        $this->testRule(
            RenameClassFixer::class,
            self::FIXTURES_PATH . '/services.yml',
            self::FIXTURES_PATH . '/ExpectedResults/rename_class_services.yml',
            $ruleConfiguration
        );
    }
}
