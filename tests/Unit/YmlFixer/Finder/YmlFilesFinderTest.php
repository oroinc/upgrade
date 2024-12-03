<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Finder;

use Oro\UpgradeToolkit\YmlFixer\Finder\YmlFilesFinder;
use PHPUnit\Framework\TestCase;

class YmlFilesFinderTest extends TestCase
{
    private const FIXTURES_PATH = './vendor/oro/upgrade-toolkit/tests/Unit/YmlFixer/Fixtures/YmlFilesFinder';
    private YmlFilesFinder $ymlFilesFinder;

    protected function setUp(): void
    {
        $this->ymlFilesFinder = new YmlFilesFinder();
    }

    public function testFindAllYmlFiles(): void
    {
        $expectedResult = [
            realpath(self::FIXTURES_PATH . '/test_file.yml'),
            realpath(self::FIXTURES_PATH . '/empty_file.yml'),
        ];
        $searchDir = 'vendor/oro/upgrade-toolkit/tests/Unit/YmlFixer/Fixtures/YmlFilesFinder';
        $actualResult = $this->ymlFilesFinder->findAllYmlFiles($searchDir);

        $this->assertSame(sort($expectedResult), sort($actualResult));
    }

    public function testFind(): void
    {
        $expectedResult = [
            realpath(self::FIXTURES_PATH . '/test_file.yml'),
        ];
        $searchDir = 'vendor/oro/upgrade-toolkit/tests/Unit/YmlFixer/Fixtures/YmlFilesFinder';
        $actualResult = $this->ymlFilesFinder->find(
            searchDir: $searchDir,
            filename: '*.yml',
            contentPattern: 'This test-file should be detected while testing'
        );

        $this->assertSame($expectedResult, $actualResult);
    }
}
