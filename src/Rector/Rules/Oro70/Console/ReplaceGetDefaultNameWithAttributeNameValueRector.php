<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\Console;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Replace Command::getDefaultName() with literal string from #[AsCommand(name: "...")]
 */
final class ReplaceGetDefaultNameWithAttributeNameValueRector extends AbstractRector
{
    private const GET_DEFAULT_NAME = 'getDefaultName';

    public function getNodeTypes(): array
    {
        return [StaticCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node->name, self::GET_DEFAULT_NAME)) {
            return null;
        }

        if (null === $node->class) {
            return null;
        }

        $className = $node->class;
        if ($className instanceof FullyQualified) {
            $commandClass = $className->name;
            if (null !== $commandClass) {
                $reflectedClass = new \ReflectionClass($commandClass);
                if ($reflectedClass->isSubclassOf(Command::class)) {
                    $commandName = $this->getCommandName($reflectedClass);
                    if ($commandName) {
                        return new String_($commandName);
                    }
                }
            }
        }

        return null;
    }

    private function getCommandName(\ReflectionClass $reflectedClass): ?string
    {
        $attribute = ($reflectedClass->getAttributes(AsCommand::class)[0] ?? null)?->newInstance();

        $commandName = $attribute?->name;
        if (is_string($commandName)) {
            // Filter aliases
            // Check AsCommand::_construct to get more detail
            $commandName = explode('|', $commandName);
            $commandName = current(array_filter($commandName, fn ($name) => $name !== ''));
        }

        return $commandName;
    }
}
