<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Integration;

use Oro\UpgradeToolkit\Semadiff\Classifier\ChangeClassifier;
use Oro\UpgradeToolkit\Semadiff\Comparator\FileComparator;
use PHPUnit\Framework\TestCase;

/**
 * Tests classification edge cases and patterns not covered by KnownFilesTest fixtures.
 */
final class KnownPatternsTest extends TestCase
{
    private FileComparator $comparator;
    private ChangeClassifier $classifier;

    protected function setUp(): void
    {
        $this->comparator = new FileComparator();
        $this->classifier = new ChangeClassifier();
    }

    private function classify(string $before, string $after): string
    {
        $result = $this->comparator->compare($before, $after);
        return $this->classifier->classify($result);
    }

    // ===== SIGNATURE PATTERNS =====

    /**
     * Pattern: Annotation to attribute migration (adding param type + return type).
     */
    public function testSignatureAnnotationToAttributeMigration(): void
    {
        $before = '<?php
class ProductSelector extends AbstractWidget {
    /**
     * @param array $data
     * @return void
     */
    public function setParameters($data)
    {
        $this->data = $data;
    }
}';
        $after = '<?php
class ProductSelector extends AbstractWidget {
    #[\Override]
    public function setParameters(array $data): void
    {
        $this->data = $data;
    }
}';
        $this->assertSame(ChangeClassifier::SIGNATURE, $this->classify($before, $after));
    }

    // ===== LOGIC PATTERNS =====

    /**
     * Pattern: Large refactor with new methods.
     */
    public function testLogicLargeRefactorNewMethods(): void
    {
        $before = '<?php
class Foo {
    public function process(): void
    {
        $data = $this->loadData();
        foreach ($data as $item) {
            $this->handle($item);
        }
    }

    private function handle($item): void
    {
        $item->save();
    }
}';
        $after = '<?php
class Foo {
    public function process(): void
    {
        $data = $this->loadData();
        $batched = $this->batchItems($data);
        foreach ($batched as $batch) {
            $this->processBatch($batch);
        }
    }

    private function batchItems(array $data): array
    {
        return array_chunk($data, 100);
    }

    private function processBatch(array $batch): void
    {
        foreach ($batch as $item) {
            $item->validate();
            $item->save();
        }
    }
}';
        $this->assertSame(ChangeClassifier::LOGIC, $this->classify($before, $after));
    }

    /**
     * Pattern: Migration body changes (argument values changed, new call added).
     */
    public function testLogicMigrationChanges(): void
    {
        $before = '<?php
class AddLeadFields implements Migration {
    public function up(Schema $schema): void
    {
        $table = $schema->getTable("lead");
        $table->addColumn("status", "string", ["length" => 50]);
    }
}';
        $after = '<?php
class AddLeadFields implements Migration {
    public function up(Schema $schema): void
    {
        $table = $schema->getTable("lead");
        $table->addColumn("status", "string", ["length" => 100]);
        $table->addColumn("priority", "integer", ["notnull" => false]);
    }
}';
        $this->assertSame(ChangeClassifier::LOGIC, $this->classify($before, $after));
    }

    /**
     * Pattern: Constant value change = logic.
     */
    public function testLogicConstantValueChange(): void
    {
        $before = '<?php
class Config {
    public const MAX_RETRIES = 3;
}';
        $after = '<?php
class Config {
    public const MAX_RETRIES = 5;
}';
        $this->assertSame(ChangeClassifier::LOGIC, $this->classify($before, $after));
    }

    /**
     * Pattern: Property default value change = logic.
     */
    public function testLogicPropertyDefaultChange(): void
    {
        $before = '<?php
class Foo {
    private int $timeout = 30;
}';
        $after = '<?php
class Foo {
    private int $timeout = 60;
}';
        $this->assertSame(ChangeClassifier::LOGIC, $this->classify($before, $after));
    }

    /**
     * Pattern: Class inheritance change = logic.
     */
    public function testLogicExtendsChanged(): void
    {
        $before = '<?php
class Foo extends OldBase {
    public function bar(): void {}
}';
        $after = '<?php
class Foo extends NewBase {
    public function bar(): void {}
}';
        $this->assertSame(ChangeClassifier::LOGIC, $this->classify($before, $after));
    }

    /**
     * Pattern: implements change = logic.
     */
    public function testLogicImplementsChanged(): void
    {
        $before = '<?php
class Foo implements FooInterface {
    public function bar(): void {}
}';
        $after = '<?php
class Foo implements FooInterface, NewInterface {
    public function bar(): void {}
}';
        $this->assertSame(ChangeClassifier::LOGIC, $this->classify($before, $after));
    }

    // ===== EDGE CASES =====

    /**
     * Edge case: Parse error in after code → classify as LOGIC.
     */
    public function testParseErrorClassifiedAsLogic(): void
    {
        $before = '<?php class Foo { public function bar(): void {} }';
        $after = '<?php class { invalid code here }}}';

        $this->assertSame(ChangeClassifier::LOGIC, $this->classify($before, $after));
    }

    /**
     * Edge case: Identical content → COSMETIC (no changes detected).
     */
    public function testIdenticalContentIsCosmetic(): void
    {
        $code = '<?php
class Foo {
    public function bar(): void {
        echo "hello";
    }
}';
        $this->assertSame(ChangeClassifier::COSMETIC, $this->classify($code, $code));
    }

    /**
     * Edge case: Only whitespace/formatting changes → COSMETIC.
     */
    public function testWhitespaceOnlyIsCosmetic(): void
    {
        $before = '<?php
class Foo {
    public function bar(): void {
        echo "hello";
        echo "world";
    }
}';
        $after = '<?php
class Foo {
    public function bar(): void
    {
        echo "hello";

        echo "world";
    }
}';
        $this->assertSame(ChangeClassifier::COSMETIC, $this->classify($before, $after));
    }
}
