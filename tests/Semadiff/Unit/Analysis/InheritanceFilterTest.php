<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Analysis;

use Oro\UpgradeToolkit\Semadiff\Analysis\InheritanceFilter;
use Oro\UpgradeToolkit\Tests\Semadiff\Support\TempDirTrait;
use PHPUnit\Framework\TestCase;

final class InheritanceFilterTest extends TestCase
{
    use TempDirTrait;

    private InheritanceFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new InheritanceFilter();
        $this->setUpTempDir();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDir();
    }

    public function testMethodRemovedButExistsOnParentIsFiltered(): void
    {
        $this->writeClassFile('App/ParentClass', <<<'PHP'
            <?php
            namespace App;
            class ParentClass {
                public function getName(): string { return ''; }
            }
            PHP);

        $this->writeClassFile('App/ChildClass', <<<'PHP'
            <?php
            namespace App;
            class ChildClass extends ParentClass {
            }
            PHP);

        $details = [
            'Method removed: ChildClass::getName',
            'Method body changed: ChildClass::doWork',
        ];

        $warnings = [];
        $result = $this->filter->filterInheritedRemovals(
            $details,
            $this->tmpDir,
            'App\\ChildClass',
            null,
            $warnings,
        );

        $this->assertSame(['Method body changed: ChildClass::doWork'], $result);
        $this->assertSame([], $warnings);
    }

    public function testMethodRemovedNotOnParentIsKept(): void
    {
        $this->writeClassFile('App/ParentClass', <<<'PHP'
            <?php
            namespace App;
            class ParentClass {
            }
            PHP);

        $this->writeClassFile('App/ChildClass', <<<'PHP'
            <?php
            namespace App;
            class ChildClass extends ParentClass {
            }
            PHP);

        $details = [
            'Method removed: ChildClass::getName',
        ];

        $warnings = [];
        $result = $this->filter->filterInheritedRemovals(
            $details,
            $this->tmpDir,
            'App\\ChildClass',
            null,
            $warnings,
        );

        $this->assertSame(['Method removed: ChildClass::getName'], $result);
    }

    public function testPropertyRemovedButInheritedIsFiltered(): void
    {
        $this->writeClassFile('App/ParentClass', <<<'PHP'
            <?php
            namespace App;
            class ParentClass {
                protected string $label = '';
            }
            PHP);

        $this->writeClassFile('App/ChildClass', <<<'PHP'
            <?php
            namespace App;
            class ChildClass extends ParentClass {
            }
            PHP);

        $details = [
            'Property removed: ChildClass::$label',
        ];

        $warnings = [];
        $result = $this->filter->filterInheritedRemovals(
            $details,
            $this->tmpDir,
            'App\\ChildClass',
            null,
            $warnings,
        );

        $this->assertSame([], $result);
    }

    public function testConstantRemovedButInheritedIsFiltered(): void
    {
        $this->writeClassFile('App/ParentClass', <<<'PHP'
            <?php
            namespace App;
            class ParentClass {
                public const VERSION = '1.0';
            }
            PHP);

        $this->writeClassFile('App/ChildClass', <<<'PHP'
            <?php
            namespace App;
            class ChildClass extends ParentClass {
            }
            PHP);

        $details = [
            'Constant removed: ChildClass::VERSION',
        ];

        $warnings = [];
        $result = $this->filter->filterInheritedRemovals(
            $details,
            $this->tmpDir,
            'App\\ChildClass',
            null,
            $warnings,
        );

        $this->assertSame([], $result);
    }

    public function testNoParentClassReturnsUnchanged(): void
    {
        $this->writeClassFile('App/SoloClass', <<<'PHP'
            <?php
            namespace App;
            class SoloClass {
            }
            PHP);

        $details = [
            'Method removed: SoloClass::getName',
        ];

        $warnings = [];
        $result = $this->filter->filterInheritedRemovals(
            $details,
            $this->tmpDir,
            'App\\SoloClass',
            null,
            $warnings,
        );

        $this->assertSame(['Method removed: SoloClass::getName'], $result);
        $this->assertSame([], $warnings);
    }

    public function testParentFileNotFoundReturnsUnchangedWithWarning(): void
    {
        $this->writeClassFile('App/ChildClass', <<<'PHP'
            <?php
            namespace App;
            class ChildClass extends MissingParent {
            }
            PHP);

        $details = [
            'Method removed: ChildClass::getName',
        ];

        $warnings = [];
        $result = $this->filter->filterInheritedRemovals(
            $details,
            $this->tmpDir,
            'App\\ChildClass',
            null,
            $warnings,
        );

        $this->assertSame(['Method removed: ChildClass::getName'], $result);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Could not load parent class', $warnings[0]);
    }

    public function testPrivateMemberOnParentIsNotFiltered(): void
    {
        $this->writeClassFile('App/ParentClass', <<<'PHP'
            <?php
            namespace App;
            class ParentClass {
                private function getName(): string { return ''; }
            }
            PHP);

        $this->writeClassFile('App/ChildClass', <<<'PHP'
            <?php
            namespace App;
            class ChildClass extends ParentClass {
            }
            PHP);

        $details = [
            'Method removed: ChildClass::getName',
        ];

        $warnings = [];
        $result = $this->filter->filterInheritedRemovals(
            $details,
            $this->tmpDir,
            'App\\ChildClass',
            null,
            $warnings,
        );

        $this->assertSame(['Method removed: ChildClass::getName'], $result);
    }

    public function testNoRemovedDetailsReturnsUnchanged(): void
    {
        $details = [
            'Method body changed: SomeClass::doWork',
            'Method added: SomeClass::newMethod',
        ];

        $warnings = [];
        $result = $this->filter->filterInheritedRemovals(
            $details,
            $this->tmpDir,
            'App\\SomeClass',
            null,
            $warnings,
        );

        $this->assertSame($details, $result);
    }

    public function testMixedDetailsOnlyFilterInheritedRemovals(): void
    {
        $this->writeClassFile('App/ParentClass', <<<'PHP'
            <?php
            namespace App;
            class ParentClass {
                public function getName(): string { return ''; }
            }
            PHP);

        $this->writeClassFile('App/ChildClass', <<<'PHP'
            <?php
            namespace App;
            class ChildClass extends ParentClass {
            }
            PHP);

        $details = [
            'Method removed: ChildClass::getName',
            'Method removed: ChildClass::getTitle',
            'Method body changed: ChildClass::doWork',
        ];

        $warnings = [];
        $result = $this->filter->filterInheritedRemovals(
            $details,
            $this->tmpDir,
            'App\\ChildClass',
            null,
            $warnings,
        );

        $this->assertSame([
            'Method removed: ChildClass::getTitle',
            'Method body changed: ChildClass::doWork',
        ], $result);
    }

    private function writeClassFile(string $fqcn, string $code): void
    {
        $relativePath = str_replace('\\', '/', $fqcn) . '.php';
        $filePath = $this->tmpDir . '/' . $relativePath;
        $dir = dirname($filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, $code);
    }

}
