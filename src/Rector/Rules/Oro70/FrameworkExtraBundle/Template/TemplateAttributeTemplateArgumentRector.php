<?php

declare (strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Doctrine\NodeAnalyzer\AttributeFinder;
use Rector\Rector\AbstractRector;
use Rector\ValueObject\PhpVersionFeature;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;

/**
 * Adds 'template' argument to #[Template] attributes in controller methods.
 * Auto-generates template paths for empty attributes using Oro conventions.
 *
 * Converts #[Template] to #[Template('path')].
 */
final class TemplateAttributeTemplateArgumentRector extends AbstractRector implements MinPhpVersionInterface
{
    private const TEMPLATE_CLASSES = [
        'Symfony\Bridge\Twig\Attribute\Template',
        'Sensio\Bundle\FrameworkExtraBundle\Configuration\Template'
    ];

    private const CONTROLLER_SUFFIX = 'Controller';
    private const ACTION_SUFFIX = 'Action';
    private const BUNDLE_SUFFIX = 'Bundle';
    private const BRIDGE_KEYWORD = 'Bridge';
    private const CONTROLLER_NAMESPACE_PATTERN = '\\Controller\\';
    private const ABSTRACT_CONTROLLER_CLASS = 'Symfony\\Bundle\\FrameworkBundle\\Controller\\AbstractController';
    private const TEMPLATE_EXTENSION = '.html.twig';

    private readonly ReflectionProvider $reflectionProvider;
    private readonly AttributeFinder $attributeFinder;

    public function __construct(
        ReflectionProvider $reflectionProvider,
        AttributeFinder $attributeFinder
    ) {
        $this->reflectionProvider = $reflectionProvider;
        $this->attributeFinder = $attributeFinder;
    }

    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        /** @var Class_ $node */
        if ($node->isAbstract() || !$this->isController($node)) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $this->processTemplateAttribute($node, $stmt);
            }
        }

        return $node;
    }

    private function processTemplateAttribute(Class_ $classNode, ClassMethod $method): void
    {
        $templateAttribute = $this->resolveTemplateAttribute($method);
        if (!$templateAttribute) {
            return;
        }

        if (empty($templateAttribute->args)) {
            $this->addGeneratedTemplateArgument($classNode, $method, $templateAttribute);
        } else {
            $this->addArgumentToExistingTemplate($templateAttribute);
        }
    }

    private function addGeneratedTemplateArgument(Class_ $classNode, ClassMethod $method, Attribute $templateAttribute): void
    {
        $generatedTemplateName = new String_($this->generateTemplateName($classNode, $method));
        $templateAttribute->args[] = new Arg(
            $generatedTemplateName,
            false,
            false,
            [],
            null,
        );
    }

    private function addArgumentToExistingTemplate(Attribute $templateAttribute): void
    {
        foreach ($templateAttribute->args as $arg) {
            if ($arg->value instanceof String_ && str_contains($arg->value->value, '@')) {
                $templateAttribute->args[0] = new Arg(
                    $arg->value,
                    false,
                    false,
                    [],
                    null,
                );
                break;
            }
        }
    }

    private function isController(Node $classNode): bool
    {
        if ($classNode->name && str_ends_with($classNode->name->toString(), self::CONTROLLER_SUFFIX)) {
            return true;
        }

        if ($classNode->extends) {
            $parentClassName = $this->nodeNameResolver->getName($classNode->extends);
            if ($parentClassName) {
                $baseClass = self::ABSTRACT_CONTROLLER_CLASS;
                if ($parentClassName === $baseClass || $this->isSubclassOf($parentClassName, $baseClass)) {
                    return true;
                }
            }
        }

        if ($classNode->namespacedName) {
            $namespace = $classNode->namespacedName->toString();
            if (str_contains($namespace, self::CONTROLLER_NAMESPACE_PATTERN)) {
                return true;
            }
        }

        return false;
    }

    private function isSubclassOf(string $className, string $parentClassName): bool
    {
        if (!$this->reflectionProvider->hasClass($className) || !$this->reflectionProvider->hasClass($parentClassName)) {
            return false;
        }

        try {
            return $this->reflectionProvider->getClass($className)
                ->isSubclassOfClass(new ClassReflection($parentClassName));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveTemplateAttribute(ClassMethod $stmt): ?Attribute
    {
        foreach (self::TEMPLATE_CLASSES as $templateClass) {
            $templateAttribute = $this->attributeFinder->findAttributeByClass($stmt, $templateClass);
            if ($templateAttribute) {
                return $templateAttribute;
            }
        }

        return null;
    }

    private function generateTemplateName(Class_ $classNode, ClassMethod $method): string
    {
        $action = $this->extractActionName($method);
        $parts = explode('\\', $classNode->namespacedName->name);
        $prefix = $this->buildPrefix($parts);
        $controller = $this->extractControllerName($parts);

        return sprintf('@%s/%s/%s%s', $prefix, $controller, $action, self::TEMPLATE_EXTENSION);
    }

    private function extractActionName(ClassMethod $method): string
    {
        $action = $method->name->name;
        if (str_ends_with($action, self::ACTION_SUFFIX)) {
            return substr($action, 0, -strlen(self::ACTION_SUFFIX));
        }
        return $action;
    }

    private function buildPrefix(array $namespaceParts): string
    {
        $prefix = $namespaceParts[0];
        $bundle = $this->findBundleName($namespaceParts);

        if ($bundle) {
            return $prefix . $bundle;
        }

        $bridge = $this->findBridgeName($namespaceParts);
        if ($bridge) {
            return $prefix . $bridge . 'Bridge';
        }

        return $prefix;
    }

    private function findBundleName(array $parts): ?string
    {
        foreach (array_reverse($parts) as $part) {
            if (str_ends_with($part, self::BUNDLE_SUFFIX)) {
                return substr($part, 0, -strlen(self::BUNDLE_SUFFIX));
            }
        }
        return null;
    }

    private function findBridgeName(array $parts): ?string
    {
        $bridgeIndex = array_search(self::BRIDGE_KEYWORD, $parts, true);
        if ($bridgeIndex !== false && isset($parts[$bridgeIndex + 1])) {
            return $parts[$bridgeIndex + 1];
        }
        return null;
    }

    private function extractControllerName(array $parts): ?string
    {
        foreach (array_reverse($parts) as $part) {
            if (str_ends_with($part, self::CONTROLLER_SUFFIX)) {
                return substr($part, 0, -strlen(self::CONTROLLER_SUFFIX));
            }
        }
        return null;
    }
}
