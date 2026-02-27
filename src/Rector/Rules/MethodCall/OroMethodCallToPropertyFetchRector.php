<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\MethodCall;

use Oro\UpgradeToolkit\Rector\MethodCall\ValueObject\MethodCallToPropertyFetch;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use RectorPrefix202602\Webmozart\Assert\Assert;

/**
 * Modified copy of \Rector\Transform\Rector\MethodCall\MethodCallToPropertyFetchRector, Rector v2.1.2
 *
 * Transforms method calls to property fetch or property assignment depending
 * on whether the method call has arguments or not
 *
 * Example: $template->setTemplate($value) => $template->template = $value
 *
 * Copyright (c) 2017-present Tomáš Votruba (https://tomasvotruba.cz)
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 */
final class OroMethodCallToPropertyFetchRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $methodCallsToPropertyFetches = [];

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        foreach ($this->methodCallsToPropertyFetches as $methodCallToPropertyFetch) {
            if (!$this->isName($node->name, $methodCallToPropertyFetch->getOldMethod())) {
                continue;
            }

            if (!$this->isObjectType($node->var, $methodCallToPropertyFetch->getOldObjectType())) {
                continue;
            }

            // No arguments - convert to property fetch
            if (0 === count($node->args)) {
                return $this->nodeFactory->createPropertyFetch(
                    $node->var,
                    $methodCallToPropertyFetch->getNewProperty()
                );
            }

            // With arguments - convert to property assignment
            return new Node\Expr\Assign(
                $this->nodeFactory->createPropertyFetch(
                    $node->var,
                    $methodCallToPropertyFetch->getNewProperty()
                ),
                $node->args[0]->value
            );
        }

        return null;
    }

    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, MethodCallToPropertyFetch::class);
        $this->methodCallsToPropertyFetches = $configuration;
    }
}
