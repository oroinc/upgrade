<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Normalizer;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\ParserFactory;
use Oro\UpgradeToolkit\Semadiff\Normalizer\SemanticHasher;
use PHPUnit\Framework\TestCase;

final class SemanticHasherTest extends TestCase
{
    private SemanticHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new SemanticHasher();
    }

    /**
     * THE CRITICAL BUG FIX TEST:
     * Same method calls must hash equally; different ones must not.
     */
    public function testMethodCallHashing(): void
    {
        $getId1 = new Expr\MethodCall(
            new Expr\Variable('obj'),
            new Identifier('getId'),
        );
        $getId2 = new Expr\MethodCall(
            new Expr\Variable('obj'),
            new Identifier('getId'),
        );
        $getInternalId = new Expr\MethodCall(
            new Expr\Variable('obj'),
            new Identifier('getInternalId'),
        );

        $this->assertEquals(
            $this->hasher->hash($getId1),
            $this->hasher->hash($getId2),
        );
        $this->assertNotEquals(
            $this->hasher->hash($getId1),
            $this->hasher->hash($getInternalId),
            'getId() and getInternalId() must produce different hashes',
        );
    }

    /**
     * Function calls with and without leading backslash should hash identically.
     */
    public function testLeadingBackslashNormalization(): void
    {
        // json_decode($x)
        $withoutBackslash = new Expr\FuncCall(
            new Name('json_decode'),
            [new Arg(new Expr\Variable('x'))],
        );

        // \json_decode($x)
        $withBackslash = new Expr\FuncCall(
            new Name\FullyQualified('json_decode'),
            [new Arg(new Expr\Variable('x'))],
        );

        $this->assertEquals(
            $this->hasher->hash($withoutBackslash),
            $this->hasher->hash($withBackslash),
            '\json_decode($x) and json_decode($x) must hash identically',
        );
    }

    /**
     * Different function names must produce different hashes.
     */
    public function testDifferentFunctionNamesProduceDifferentHashes(): void
    {
        $jsonDecode = new Expr\FuncCall(
            new Name('json_decode'),
            [new Arg(new Expr\Variable('x'))],
        );

        $jsonEncode = new Expr\FuncCall(
            new Name('json_encode'),
            [new Arg(new Expr\Variable('x'))],
        );

        $this->assertNotEquals(
            $this->hasher->hash($jsonDecode),
            $this->hasher->hash($jsonEncode),
        );
    }

    /**
     * Same string values must hash equally; different ones must not.
     */
    public function testStringLiteralHashing(): void
    {
        $hello1 = new Scalar\String_('hello');
        $hello2 = new Scalar\String_('hello');
        $world = new Scalar\String_('world');

        $this->assertEquals(
            $this->hasher->hash($hello1),
            $this->hasher->hash($hello2),
        );
        $this->assertNotEquals(
            $this->hasher->hash($hello1),
            $this->hasher->hash($world),
        );
    }

    /**
     * Different numeric values produce different hashes.
     */
    public function testNumericValuesIncludedInHash(): void
    {
        $one = new Scalar\Int_(1);
        $two = new Scalar\Int_(2);

        $this->assertNotEquals(
            $this->hasher->hash($one),
            $this->hasher->hash($two),
        );
    }

    /**
     * Class constant fetches must include class and constant name.
     */
    public function testClassConstFetchIncludesNames(): void
    {
        // Foo::BAR
        $fooBar = new Expr\ClassConstFetch(
            new Name('Foo'),
            new Identifier('BAR'),
        );

        // Foo::BAZ
        $fooBaz = new Expr\ClassConstFetch(
            new Name('Foo'),
            new Identifier('BAZ'),
        );

        // Baz::BAR
        $bazBar = new Expr\ClassConstFetch(
            new Name('Baz'),
            new Identifier('BAR'),
        );

        $this->assertNotEquals($this->hasher->hash($fooBar), $this->hasher->hash($fooBaz));
        $this->assertNotEquals($this->hasher->hash($fooBar), $this->hasher->hash($bazBar));
    }

    /**
     * Property fetches must include property name.
     */
    public function testPropertyFetchIncludesName(): void
    {
        // $this->foo
        $thisFoo = new Expr\PropertyFetch(
            new Expr\Variable('this'),
            new Identifier('foo'),
        );

        // $this->bar
        $thisBar = new Expr\PropertyFetch(
            new Expr\Variable('this'),
            new Identifier('bar'),
        );

        $this->assertNotEquals(
            $this->hasher->hash($thisFoo),
            $this->hasher->hash($thisBar),
        );
    }

    /**
     * Static calls must differentiate class and method names.
     */
    public function testStaticCallsDifferentiate(): void
    {
        // Foo::bar()
        $fooBar = new Expr\StaticCall(
            new Name('Foo'),
            new Identifier('bar'),
        );

        // Foo::baz()
        $fooBaz = new Expr\StaticCall(
            new Name('Foo'),
            new Identifier('baz'),
        );

        $this->assertNotEquals(
            $this->hasher->hash($fooBar),
            $this->hasher->hash($fooBaz),
        );
    }

    /**
     * Test with full parsed AST - the real-world scenario.
     * EnumHelper::getSafeInternalId($x) vs $x->getId() must be different.
     */
    public function testRealWorldMethodCallDifference(): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $code1 = '<?php $result = $entity->getId();';
        $code2 = '<?php $result = EnumHelper::getSafeInternalId($entity);';

        $ast1 = $parser->parse($code1);
        $ast2 = $parser->parse($code2);

        $this->assertNotEquals(
            $this->hasher->hash($ast1),
            $this->hasher->hash($ast2),
            'Direct method call vs static helper call must differ',
        );
    }

    /**
     * Test string constant reference change (logic change).
     * 'OroCustomerBundle:CustomerUser' vs CustomerUser::class
     */
    public function testStringVsClassConstantDifference(): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $code1 = "<?php \$x = 'OroCustomerBundle:CustomerUser';";
        $code2 = '<?php $x = CustomerUser::class;';

        $ast1 = $parser->parse($code1);
        $ast2 = $parser->parse($code2);

        $this->assertNotEquals(
            $this->hasher->hash($ast1),
            $this->hasher->hash($ast2),
            'String literal vs ::class constant must produce different hashes',
        );
    }

    /**
     * Null and empty array produce consistent, non-empty hashes.
     */
    public function testEdgeCaseInputs(): void
    {
        $nullHash = $this->hasher->hash(null);
        $this->assertNotEmpty($nullHash);
        $this->assertEquals($nullHash, $this->hasher->hash(null));

        $emptyHash = $this->hasher->hash([]);
        $this->assertNotEmpty($emptyHash);
    }
}
