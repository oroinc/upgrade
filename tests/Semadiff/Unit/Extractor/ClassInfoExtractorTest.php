<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Extractor;

use Oro\UpgradeToolkit\Semadiff\Extractor\ClassInfoExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClassInfoExtractorTest extends TestCase
{
    private ClassInfoExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new ClassInfoExtractor();
    }

    public function testExtractsClassNameAndType(): void
    {
        $code = '<?php
namespace App;
class Foo extends Bar implements Baz {
    use SomeTrait;
}';
        $classes = $this->extractor->extract($code);
        $this->assertCount(1, $classes);
        $this->assertSame('Foo', $classes[0]->name);
        $this->assertSame('class', $classes[0]->type);
        $this->assertSame('Bar', $classes[0]->extends);
        $this->assertSame(['Baz'], $classes[0]->implements);
        $this->assertSame(['SomeTrait'], $classes[0]->traits);
    }

    public function testExtractsMethodSignature(): void
    {
        $code = '<?php
class Foo {
    protected static function doSomething(string $name, int $age = 0): bool
    {
        return true;
    }
}';
        $classes = $this->extractor->extract($code);
        $method = $classes[0]->getMethod('doSomething');
        $this->assertNotNull($method);
        $this->assertSame('protected', $method->visibility);
        $this->assertTrue($method->isStatic);
        $this->assertSame('bool', $method->returnType);
        $this->assertCount(2, $method->params);
        $this->assertSame('name', $method->params[0]['name']);
        $this->assertSame('string', $method->params[0]['type']);
    }

    public function testExtractsProperties(): void
    {
        $code = '<?php
class Foo {
    private readonly string $name;
    public static int $count = 0;
}';
        $classes = $this->extractor->extract($code);
        $this->assertCount(2, $classes[0]->properties);

        $nameProp = $classes[0]->getProperty('name');
        $this->assertNotNull($nameProp);
        $this->assertSame('private', $nameProp->visibility);
        $this->assertTrue($nameProp->isReadonly);
        $this->assertSame('string', $nameProp->type);

        $countProp = $classes[0]->getProperty('count');
        $this->assertNotNull($countProp);
        $this->assertTrue($countProp->isStatic);
    }

    public function testExtractsConstants(): void
    {
        $code = '<?php
class Foo {
    public const string NAME = "foo";
    private const int MAX = 100;
    final public const X = 1;
}';
        $classes = $this->extractor->extract($code);
        $this->assertCount(3, $classes[0]->constants);

        $nameConst = $classes[0]->getConstant('NAME');
        $this->assertNotNull($nameConst);
        $this->assertSame('public', $nameConst->visibility);
        $this->assertFalse($nameConst->isFinal);

        $finalConst = $classes[0]->getConstant('X');
        $this->assertNotNull($finalConst);
        $this->assertTrue($finalConst->isFinal);
    }

    public function testStripsOverrideFromMethods(): void
    {
        $codeBefore = '<?php
class Foo {
    public function bar(): void {
        echo "hello";
    }
}';
        $codeAfter = '<?php
class Foo {
    #[\Override]
    public function bar(): void {
        echo "hello";
    }
}';

        $beforeClasses = $this->extractor->extract($codeBefore);
        $afterClasses = $this->extractor->extract($codeAfter);

        $beforeMethod = $beforeClasses[0]->getMethod('bar');
        $afterMethod = $afterClasses[0]->getMethod('bar');

        // Body hash should be the same since #[\Override] is stripped
        $this->assertSame($beforeMethod->bodyHash, $afterMethod->bodyHash);
        // Signature should also be the same
        $this->assertTrue($beforeMethod->signatureEquals($afterMethod));
    }

    public function testBodyHashChangesOnMethodCallRename(): void
    {
        $codeBefore = '<?php
class Foo {
    public function bar(): int {
        return $this->entity->getId();
    }
}';
        $codeAfter = '<?php
class Foo {
    public function bar(): int {
        return $this->entity->getInternalId();
    }
}';

        $beforeClasses = $this->extractor->extract($codeBefore);
        $afterClasses = $this->extractor->extract($codeAfter);

        $beforeMethod = $beforeClasses[0]->getMethod('bar');
        $afterMethod = $afterClasses[0]->getMethod('bar');

        $this->assertNotSame(
            $beforeMethod->bodyHash,
            $afterMethod->bodyHash,
            'getId() vs getInternalId() must produce different body hashes',
        );
    }

    public function testCommentOnlyChangeProducesSameHash(): void
    {
        $codeBefore = '<?php
class Foo {
    /**
     * @inheritdoc
     */
    public function bar(): void {
        echo "hello";
    }
}';
        $codeAfter = '<?php
class Foo {
    public function bar(): void {
        echo "hello";
    }
}';

        $beforeClasses = $this->extractor->extract($codeBefore);
        $afterClasses = $this->extractor->extract($codeAfter);

        $this->assertSame(
            $beforeClasses[0]->getMethod('bar')->bodyHash,
            $afterClasses[0]->getMethod('bar')->bodyHash,
        );
    }

    public function testExtractsInterface(): void
    {
        $code = '<?php
interface FooInterface extends BarInterface, BazInterface {
    public function doStuff(): void;
}';
        $classes = $this->extractor->extract($code);
        $this->assertCount(1, $classes);
        $this->assertSame('interface', $classes[0]->type);
        $this->assertSame('BarInterface,BazInterface', $classes[0]->extends);
        $this->assertFalse($classes[0]->isFinal);
    }

    public function testExtractsTrait(): void
    {
        $code = '<?php
trait FooTrait {
    public function doStuff(): void {
        echo "trait method";
    }
}';
        $classes = $this->extractor->extract($code);
        $this->assertCount(1, $classes);
        $this->assertSame('trait', $classes[0]->type);
        $this->assertFalse($classes[0]->isFinal);
    }

    public function testExtractsTopLevelFunctions(): void
    {
        $code = '<?php
function myHelper(string $input): string {
    return strtolower($input);
}';
        $classes = $this->extractor->extract($code);
        $this->assertCount(1, $classes);
        $this->assertSame('__TOP_LEVEL_FUNCTIONS__', $classes[0]->name);
        $this->assertNotNull($classes[0]->getMethod('myHelper'));
    }

    public function testMultipleClassesInFile(): void
    {
        $code = '<?php
class Foo {
    public function fooMethod(): void {}
}
class Bar {
    public function barMethod(): void {}
}';
        $classes = $this->extractor->extract($code);
        $this->assertCount(2, $classes);
    }

    public function testPromotedConstructorProperties(): void
    {
        $code = '<?php
class Foo {
    public function __construct(
        private readonly string $name,
        protected int $age = 0,
    ) {}
}';
        $classes = $this->extractor->extract($code);
        $nameProp = $classes[0]->getProperty('name');
        $this->assertNotNull($nameProp);
        $this->assertSame('private', $nameProp->visibility);
        $this->assertTrue($nameProp->isReadonly);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function fqcnSingleTypeProvider(): iterable
    {
        yield 'class' => [
            '<?php
namespace App\Service;
class UserService {}',
            ['App\Service\UserService'],
        ];
        yield 'interface' => [
            '<?php
namespace App\Contract;
interface UserRepositoryInterface {}',
            ['App\Contract\UserRepositoryInterface'],
        ];
        yield 'trait' => [
            '<?php
namespace App\Concern;
trait Timestampable {}',
            ['App\Concern\Timestampable'],
        ];
        yield 'enum' => [
            '<?php
namespace App\Enum;
enum Status: string {
    case Active = "active";
    case Inactive = "inactive";
}',
            ['App\Enum\Status'],
        ];
    }

    /**
     * @param list<string> $expectedFqcns
     */
    #[DataProvider('fqcnSingleTypeProvider')]
    public function testExtractFqcnsSingleType(string $code, array $expectedFqcns): void
    {
        $this->assertSame($expectedFqcns, $this->extractor->extractFqcns($code));
    }

    public function testExtractFqcnsMultipleDeclarations(): void
    {
        $code = '<?php
namespace App\Model;
class Foo {}
interface BarInterface {}
trait BazTrait {}';
        $fqcns = $this->extractor->extractFqcns($code);
        $this->assertCount(3, $fqcns);
        $this->assertSame('App\Model\Foo', $fqcns[0]);
        $this->assertSame('App\Model\BarInterface', $fqcns[1]);
        $this->assertSame('App\Model\BazTrait', $fqcns[2]);
    }

    public function testExtractFqcnsGlobalNamespace(): void
    {
        $code = '<?php
class LegacyHelper {}';
        $this->assertSame(['LegacyHelper'], $this->extractor->extractFqcns($code));
    }

    public function testExtractFqcnsNoClassLikeDeclaration(): void
    {
        $code = '<?php
function helper(): void {}';
        $this->assertSame([], $this->extractor->extractFqcns($code));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function classFinalProvider(): iterable
    {
        yield 'final class' => ['<?php final class Foo {}', true];
        yield 'non-final class' => ['<?php class Foo {}', false];
    }

    #[DataProvider('classFinalProvider')]
    public function testClassFinalExtraction(string $code, bool $expectedIsFinal): void
    {
        $classes = $this->extractor->extract($code);
        $this->assertCount(1, $classes);
        $this->assertSame($expectedIsFinal, $classes[0]->isFinal);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function methodFinalProvider(): iterable
    {
        yield 'final method' => [
            '<?php class Foo { final public function bar(): void {} }',
            'bar',
            true,
        ];
        yield 'non-final method' => [
            '<?php class Foo { public function bar(): void {} }',
            'bar',
            false,
        ];
        yield 'abstract method is not final' => [
            '<?php abstract class Foo { abstract public function bar(): void; }',
            'bar',
            false,
        ];
    }

    #[DataProvider('methodFinalProvider')]
    public function testMethodFinalExtraction(string $code, string $methodName, bool $expectedIsFinal): void
    {
        $classes = $this->extractor->extract($code);
        $method = $classes[0]->getMethod($methodName);
        $this->assertNotNull($method);
        $this->assertSame($expectedIsFinal, $method->isFinal);
    }

    public function testEnumCaseIsNotFinal(): void
    {
        $code = '<?php
enum Foo {
    case A;
}';
        $classes = $this->extractor->extract($code);
        $constant = $classes[0]->getConstant('A');
        $this->assertNotNull($constant);
        $this->assertFalse($constant->isFinal);
    }

    public function testHasDefaultExtractedForAllParams(): void
    {
        $code = '<?php
class Foo {
    public function bar(string $name, int $age = 0): void {}
}';
        $classes = $this->extractor->extract($code);
        $method = $classes[0]->getMethod('bar');
        $this->assertNotNull($method);
        $this->assertCount(2, $method->params);

        $this->assertArrayHasKey('hasDefault', $method->params[0]);
        $this->assertFalse($method->params[0]['hasDefault']);

        $this->assertArrayHasKey('hasDefault', $method->params[1]);
        $this->assertTrue($method->params[1]['hasDefault']);
    }
}
