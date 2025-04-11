<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;

final class MinimizeAnnotationRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $annotationsToMinimize = [];

    #[\Override]
    public function getNodeTypes(): array
    {
        return [Class_::class, Property::class, ClassMethod::class];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        if (!$node->getDocComment()) {
            return null;
        }

        if (!$this->annotationsToMinimize) {
            throw new \LogicException(sprintf(
                ' "%s" : No input parameter provided.',
                self::class
            ));
        }

        foreach ($this->annotationsToMinimize as $annotationName) {
            $docComment = $node->getDocComment();
            $textValue = $this->minimize($docComment->getText(), $annotationName);

            if ($textValue) {
                $node->setDocComment(new Doc($textValue));
            }
        }

        return $node;
    }

    #[\Override]
    public function configure(array $configuration): void
    {
        $this->annotationsToMinimize = $configuration;
    }

    private function minimize(string $docCommentTextValue, string $annotationName): string|null
    {
        $startPos = strpos($docCommentTextValue, '@' . $annotationName . '(');
        if ($startPos) {
            $endPos = strpos($docCommentTextValue, ')', $startPos);

            $annotationSting = substr($docCommentTextValue, $startPos, ($endPos - $startPos + 1));

            if (!str_contains($annotationSting, '*')) {
                return null;
            }

            $annotationSting = str_replace('*', '', $annotationSting);
            $annotationSting = preg_replace('/\s+/', '', $annotationSting);

            $prefix = substr($docCommentTextValue, 0, ($startPos));
            $suffix = substr($docCommentTextValue, ($endPos + 1), strlen($docCommentTextValue));

            return $prefix . $annotationSting . $suffix;
        }

        return null;
    }
}
