<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Normalizer;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Oro\UpgradeToolkit\Semadiff\Normalizer\BodyNormalizer;
use PHPUnit\Framework\TestCase;

final class BodyNormalizerTest extends TestCase
{
    private function normalize(string $code): string
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new BodyNormalizer());
        $ast = $traverser->traverse($ast);

        $printer = new Standard();
        return $printer->prettyPrintFile($ast);
    }

    public function testStripsComments(): void
    {
        $before = '<?php
// This is a comment
/** Docblock */
function foo() {
    // inline comment
    return 1;
}';

        $result = $this->normalize($before);
        $this->assertStringNotContainsString('This is a comment', $result);
        $this->assertStringNotContainsString('Docblock', $result);
        $this->assertStringNotContainsString('inline comment', $result);
        $this->assertStringContainsString('return 1', $result);
    }

    public function testStripsOverrideAttribute(): void
    {
        $code = '<?php
class Foo {
    #[\Override]
    public function bar(): void {}
}';

        $result = $this->normalize($code);
        $this->assertStringNotContainsString('Override', $result);
        $this->assertStringContainsString('function bar', $result);
    }

    public function testPreservesNonOverrideAttributes(): void
    {
        $code = '<?php
class Foo {
    #[\Deprecated]
    #[\Override]
    public function bar(): void {}
}';

        $result = $this->normalize($code);
        $this->assertStringNotContainsString('Override', $result);
        $this->assertStringContainsString('Deprecated', $result);
    }
}
