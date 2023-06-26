<?php

declare(strict_types=1);

namespace Oro\Rector\TopicClass;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Return_;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TopicClassFactory
{
    public function create(string $className, string $namespacedName, string $topicValue): Class_
    {
        $class = new Class_($className, [
            'extends' => new Name('\\' . AbstractTopic::class),
            'stmts' => [
                $this->createGetNameMethod($topicValue),
                $this->createGetDescriptionMethod(),
                $this->createConfigureMessageBodyMethod()
            ]
        ]);
        $class->namespacedName = new Name($namespacedName);

        return $class;
    }

    private function createGetNameMethod(string $value): ClassMethod
    {
        return new ClassMethod(
            'getName',
            [
                'flags' => Class_::MODIFIER_PUBLIC | Class_::MODIFIER_STATIC,
                'returnType' => 'string',
                'stmts' => [
                    new Return_(new String_($value))
                ]
            ]
        );
    }

    private function createGetDescriptionMethod(): ClassMethod
    {
        $return = new Return_(new String_(''));
        $return->setDocComment(new Doc('// TODO: Implement getDescription() method.'));

        return new ClassMethod(
            'getDescription',
            [
                'flags' => Class_::MODIFIER_PUBLIC | Class_::MODIFIER_STATIC,
                'returnType' => 'string',
                'stmts' => [
                    $return
                ]
            ]
        );
    }

    public function createConfigureMessageBodyMethod(): ClassMethod
    {
        $nop = new Nop();
        $nop->setDocComment(new Doc('// TODO: Implement configureMessageBody() method.'));

        return new ClassMethod(
            'configureMessageBody',
            [
                'flags' => Class_::MODIFIER_PUBLIC,
                'returnType' => 'void',
                'params' => [
                    new Param(
                        new Variable('resolver'),
                        null,
                        new Identifier('\\' . OptionsResolver::class)
                    )
                ],
                'stmts' => [
                    $nop,
                ]
            ]
        );
    }
}
