<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Signature;

use Nette\Utils\FileSystem;
use Oro\UpgradeToolkit\Configuration\SignatureConfig;
use Rector\Exception\ShouldNotHappenException;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class SignatureConfiguratorTest extends AbstractRectorTestCase
{
    protected function setUp(): void
    {
        // Put signatures list to the TMP storage.
        $signaturesList = FileSystem::read(__DIR__ . '/Fixture/Signatures/signatures.php');
        FileSystem::write(sys_get_temp_dir() . '/' . SignatureConfig::FILE_NAME, $signaturesList);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Remove signatures from the TMP storage
        FileSystem::delete(sys_get_temp_dir() . '/' . SignatureConfig::FILE_NAME);

        parent::tearDown();
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function test(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/fixture.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/config.php';
    }
}
