<?php

declare(strict_types=1);

namespace Oro\Rector;

use Oro\Rector\TopicClass\TopicClassFactory;
use Oro\Rector\TopicClass\TopicClassNameGenerator;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use Rector\Core\Application\FileSystem\RemovedAndAddedFilesCollector;
use Rector\Core\PhpParser\Printer\NeighbourClassLikePrinter;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class GenerateTopicClassesRector extends AbstractRector
{
    public function __construct(
        private NeighbourClassLikePrinter $neighbourClassLikePrinter,
        private RemovedAndAddedFilesCollector $removedAndAddedFilesCollector,
        private TopicClassFactory $topicClassFactory = new TopicClassFactory(),
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Generate topic classes',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    namespace App\Async\Topics;

                    final class Topics
                    {
                        public const FIRST = 'first';
                        public const SECOND = 'second';
                    }

                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    // new file: "app/Async/FirstTopic.php"
                    namespace App\Async\Topics;

                    final class FirstTopic extends \Oro\Component\MessageQueue\Topic\AbstractTopic
                    {
                        public static function getName(): string
                        {
                            return 'first';
                        }
                        public static function getDescription(): string
                        {
                            // TODO: Implement getDescription() method.
                            return '';
                        }
                        public function configureMessageBody(\Symfony\Component\OptionsResolver\OptionsResolver $resolver): void
                        {
                            // TODO: Implement configureMessageBody() method.
                        }
                    }

                    // new file: "app/Async/SecondTopic.php"
                    namespace App\Async\Topics;

                    final class SecondTopic extends \Oro\Component\MessageQueue\Topic\AbstractTopic
                    {
                        public static function getName(): string
                        {
                            return 'second';
                        }
                        public static function getDescription(): string
                        {
                            // TODO: Implement getDescription() method.
                            return '';
                        }
                        public function configureMessageBody(\Symfony\Component\OptionsResolver\OptionsResolver $resolver): void
                        {
                            // TODO: Implement configureMessageBody() method.
                        }
                    }
                    CODE_SAMPLE
                )
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node)
    {
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
            $namespaceNode = $node->getAttribute(AttributeKey::PARENT_NODE);
            $newNamespace = clone $namespaceNode;
            $newNamespace->name = new Name($namespace);
            $newNamespace->stmts[] = $class;
            $this->printNewNodes($class, $newNamespace);
        }

        $this->removedAndAddedFilesCollector->removeFile($this->file->getFilePath());

        return $node;
    }

    private function printNewNodes(ClassLike $classLike, $mainNode): void
    {
        $filePath = $this->file->getFilePath();
        $filePath = \str_replace('Topics.php', 'Topic/Topics.php', $filePath);

        $this->neighbourClassLikePrinter->printClassLike($classLike, $mainNode, $filePath);
    }
}
