<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\OpenSpout;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;

/**
 * Replace WriterFactory::createFromType with direct OpenSpout writer instantiation
 *
 * Example:
 * - Before: WriterFactory::createFromType(Type::XLSX)
 * - After:  new XLSXWriter()
 */
final class ReplaceWriterFactoryWithDirectInstantiationRector extends AbstractRector
{
    private const WRITER_FACTORY = 'WriterFactory';
    private const FQCN_WRITER_FACTORY = 'Box\\Spout\\Writer\\Common\\Creator\\WriterFactory';
    private const METHOD_NAME = 'createFromType';

    #[\Override]
    public function getNodeTypes(): array
    {
        return [StaticCall::class];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        if (
            !$this->isName($node->class, self::WRITER_FACTORY) &&
            !$this->isName($node->class, self::FQCN_WRITER_FACTORY)
        ) {
            return null;
        }

        if (!$this->isName($node->name, self::METHOD_NAME)) {
            return null;
        }

        if (!isset($node->args[0])) {
            return null;
        }

        $writerClass = $this->resolveWriterClass($node);

        return $writerClass ? new New_(new Name($writerClass)) : null;
    }

    private function resolveWriterClass(Node $node): ?string
    {
        $writerClass = null;
        $formatName = null;

        $arg = $node->args[0]->value;
        if ($arg instanceof ClassConstFetch) {
            $formatName = $arg->name->toString();
        }

        if ($arg instanceof String_) {
            $formatName = strtoupper($arg->value);
        }

        switch ($formatName) {
            case 'CSV':
                $writerClass = '\\OpenSpout\\Writer\\CSV\\Writer';
                break;
            case 'XLSX':
                $writerClass = '\\OpenSpout\\Writer\\XLSX\\Writer';
                break;
            case 'ODS':
                $writerClass = '\\OpenSpout\\Writer\\ODS\\Writer';
                break;
        }

        return $writerClass;
    }
}
