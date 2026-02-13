<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Replace;

use Oro\UpgradeToolkit\Rector\Replacement\ArgumentReplaceHelper;
use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\MethodCallArgReplace;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Webmozart\Assert\Assert;

/**
 * Replaces a specific argument value in method/static calls.
 *
 * Config: new MethodCallArgReplace(Foo::class, 'bar', 'mode', 'old', 'new')
 * Before: $foo->bar(name: 'bar' mode: 'old');
 * After: $foo->bar(name: 'bar' mode: 'new');
 */
class ReplaceArgInMethodCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $configuration = [];

    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ArgumentReplaceHelper $argumentReplaceHelper,
    ) {
    }

    #[\Override]
    public function configure(array $configuration): void
    {
        Assert::allIsInstanceOf($configuration, MethodCallArgReplace::class);
        $this->configuration = $configuration;
    }

    #[\Override]
    public function getNodeTypes(): array
    {
        return [
            MethodCall::class,
            NullsafeMethodCall::class,
            StaticCall::class,
        ];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        $callName = $this->getName($node->name);
        if ($callName === null) {
            return null;
        }

        $hasChanged = false;

        foreach ($this->configuration as $methodCallRename) {
            if (!$this->nodeNameResolver->isStringName($callName, $methodCallRename->getMethod())) {
                continue;
            }

            if (
                !$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType(
                    $node,
                    $methodCallRename->getObjectType()
                )
            ) {
                continue;
            }

            if ($this->shouldSkip($node, $methodCallRename)) {
                continue;
            }

            $result = $this->argumentReplaceHelper->replace($node, $methodCallRename);
            if (null == $result) {
                continue;
            }

            $node = $result;
            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    private function shouldSkip($call, MethodCallArgReplace $methodCallRename): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflectionSourceObject($call);
        if (!$classReflection instanceof ClassReflection) {
            return false;
        }

        $targetClass = $methodCallRename->getClass();
        if (!$this->reflectionProvider->hasClass($targetClass)) {
            return false;
        }

        $targetClassReflection = $this->reflectionProvider->getClass($targetClass);
        if ($classReflection->getName() === $targetClassReflection->getName()) {
            return false;
        }

        if (!$classReflection->hasMethod($methodCallRename->getMethod())) {
            return false;
        }

        return $classReflection->hasMethod($methodCallRename->getMethod());
    }
}
