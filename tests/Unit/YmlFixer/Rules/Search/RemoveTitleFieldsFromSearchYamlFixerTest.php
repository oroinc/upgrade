<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Search;

use Oro\UpgradeToolkit\YmlFixer\Rules\Search\RemoveTitleFieldsFromSearchYamlFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class RemoveTitleFieldsFromSearchYamlFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            RemoveTitleFieldsFromSearchYamlFixer::class,
            self::FIXTURES_PATH . '/search.yml',
            self::FIXTURES_PATH . '/ExpectedResults/remove_title_fields_search.yml'
        );
    }
}
