<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation;

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\RenameAnnotationTag;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Exception\ShouldNotHappenException;
use Rector\Rector\AbstractRector;
use RectorPrefix202602\Webmozart\Assert\Assert;

/**
 * Automates annotation tag renaming in PHP doc blocks using a configurable mapping.
 * E.g: @Doctrine\ORM\Mapping\Column(type="integer") => @ORM\Column(type="integer")
 */
final class AnnotationTagRenameRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $renameAnnotationTags = [];

    private bool $hasChanged = false;

    #[\Override]
    public function getNodeTypes(): array
    {
        return [Class_::class, Property::class, ClassMethod::class];
    }

    /**
     * @throws ShouldNotHappenException
     */
    #[\Override]
    public function refactor(Node $node): ?Node
    {
        if (!$node->getDocComment()) {
            return null;
        }

        if (!$this->renameAnnotationTags) {
            throw new ShouldNotHappenException(
                \sprintf('%s::renameAnnotationTags property value cannot be empty', $this::class)
            );
        }

        $this->hasChanged = false;
        foreach ($this->renameAnnotationTags as $renameAnnotationTag) {
            $this->replaceTag(
                $node,
                $renameAnnotationTag->getOldTag(),
                $renameAnnotationTag->getNewTag()
            );
        }

        return $this->hasChanged ? $node : null;
    }

    #[\Override]
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, RenameAnnotationTag::class);
        $this->renameAnnotationTags = $configuration;
    }

    private function replaceTag(Node $node, string $oldTag, string $newTag): void
    {
        $docComment = $node->getDocComment();
        $docCommentTextValue = $docComment->getText();

        if (str_contains($docCommentTextValue, '@' . $oldTag)) {
            $docCommentTextValue = str_replace(
                ['* @' . $oldTag, '*@' . $oldTag],
                ['* @' . $newTag, '*@' . $newTag],
                $docCommentTextValue
            );

            $node->setDocComment(new Doc($docCommentTextValue));
            $this->hasChanged = true;
        }
    }
}
