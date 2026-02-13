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
 * Replace ReaderFactory::createFromType with direct OpenSpout reader instantiation
 *
 * Example:
 * - Before: ReaderFactory::createFromType(Type::CSV)
 * - After:  new CSVReader()
 *
 * - Before: ReaderFactory::createFromType(Type::XLSX)
 * - After:  new XLSXReader()
 */
final class ReplaceReaderFactoryWithDirectInstantiationRector extends AbstractRector
{
    private const READER_FACTORY = 'ReaderFactory';
    private const FQCN_READER_FACTORY = 'Box\\Spout\\Reader\\Common\\Creator\\ReaderFactory';
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
            !$this->isName($node->class, self::READER_FACTORY) &&
            !$this->isName($node->class, self::FQCN_READER_FACTORY)
        ) {
            return null;
        }

        if (!$this->isName($node->name, self::METHOD_NAME)) {
            return null;
        }

        if (!isset($node->args[0])) {
            return null;
        }

        $readerClass = $this->resolveReaderClass($node);

        return $readerClass ? new New_(new Name($readerClass)) : null;
    }

    private function resolveReaderClass(Node $node): ?string
    {
        $readerClass = null;
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
                $readerClass = '\\OpenSpout\\Reader\\CSV\\Reader';
                break;
            case 'XLSX':
                $readerClass = '\\OpenSpout\\Reader\\XLSX\\Reader';
                break;
            case 'ODS':
                $readerClass = '\\OpenSpout\\Reader\\ODS\\Reader';
                break;
        }

        return $readerClass;
    }
}
