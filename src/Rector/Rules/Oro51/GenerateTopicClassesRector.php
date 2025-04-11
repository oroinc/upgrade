<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro51;

use Oro\UpgradeToolkit\Rector\Application\DeletedFilesProcessor;
use Oro\UpgradeToolkit\Rector\Printer\NeighbourClassLikePrinter;
use Oro\UpgradeToolkit\Rector\TopicClass\TopicClassFactory;
use Oro\UpgradeToolkit\Rector\TopicClass\TopicClassNameGenerator;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use Rector\Rector\AbstractRector;

class GenerateTopicClassesRector extends AbstractRector
{
    public function __construct(
        private NeighbourClassLikePrinter $neighbourClassLikePrinter,
        private DeletedFilesProcessor $deletedFilesProcessor,
        private TopicClassFactory $topicClassFactory = new TopicClassFactory(),
    ) {
    }

    #[\Override]
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    #[\Override]
    public function refactor(Node $node)
    {
        if (!$node->namespacedName) {
            return false;
        }

        if (!\str_ends_with($node->namespacedName->toString(), '\\Async\\Topics')) {
            return null;
        }

        /** @var ClassConst $constant */
        foreach ($node->getConstants() as $constant) {
            $name = $constant->consts[0]->name->name;

            $value = $constant->consts[0]->value->value;

            $namespace = $node->namespacedName?->slice(0, -1) . '\\Topic';
            $topicClassName = TopicClassNameGenerator::getByConstantName($name);

            $namespacedName = $namespace . '\\' . $topicClassName;
            if (\class_exists($namespacedName)) {
                continue;
            }

            $class = $this->topicClassFactory->create($topicClassName, $namespacedName, $value);
            $newNamespace = new Namespace_();
            $newNamespace->name = new Name($namespace);
            $newNamespace->stmts[] = $class;

            $this->printNewNode($class, $newNamespace);
        }

        $this->deletedFilesProcessor->addFileToDelete($this->file->getFilePath());

        return $node;
    }

    private function printNewNode(ClassLike $classLike, $mainNode): void
    {
        $filePath = $this->file->getFilePath();
        $filePath = str_replace('Topics.php', 'Topic/Topics.php', $filePath);
        $this->neighbourClassLikePrinter->printClassLike($classLike, $mainNode, $filePath);
    }
}
