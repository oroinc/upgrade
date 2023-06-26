<?php

namespace Acme\Bundle\FooBundle\Async\Topic;

class SendEmailTopic extends \Oro\Component\MessageQueue\Topic\AbstractTopic
{
    public static function getName(): string
    {
        return 'send_email';
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
