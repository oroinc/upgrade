<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ExtendedEntityUpdateRector;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Oro\UpgradeToolkit\Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ExtendedEntityUpdateRectorTest extends AbstractRectorTestCase
{
    private ?string $modelFilePath = null;

    protected function tearDown(): void
    {
        // clear temporary file
        if (is_string($this->modelFilePath)) {
            FileSystem::delete($this->modelFilePath);
        }

        parent::tearDown();
    }

    public function testRefactor(): void
    {
        $fixtureFilePath = __DIR__ . '/Fixture/model.php.inc';
        $modelFileContents = FileSystem::read($fixtureFilePath);
        $modelFilePath = $this->createInputFilePath($fixtureFilePath);
        // to remove later in tearDown()
        $this->modelFilePath = $modelFilePath;

        // write temp file
        FileSystem::write($modelFilePath, $modelFileContents);
        require_once $modelFilePath;

        $this->doTestFile(__DIR__ . '/Fixture/fixture.php.inc');

        $isFileRemoved = !file_exists(__DIR__ . '/Fixture/model.php');
        $this->assertTrue($isFileRemoved);
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }

    private function createInputFilePath(string $fixtureFilePath): string
    {
        $inputFileDirectory = dirname($fixtureFilePath);

        // remove ".inc" suffix
        if (str_ends_with($fixtureFilePath, '.inc')) {
            $trimmedFixtureFilePath = Strings::substring($fixtureFilePath, 0, -4);
        } else {
            $trimmedFixtureFilePath = $fixtureFilePath;
        }

        $fixtureBasename = pathinfo($trimmedFixtureFilePath, \PATHINFO_BASENAME);

        return $inputFileDirectory . '/' . $fixtureBasename;
    }
}
