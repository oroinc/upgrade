<?php

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CustomEvent extends Event
{
    public const NAME = 'CustomeEvent';
}

class TestEventSubscriber
{
    private readonly EventDispatcherInterface $dispatcher;

    public function subscribe($entity)
    {
        global $args;
        $event = $this->dispatcher->dispatch(
            CustomEvent::NAME,
            new CustomEvent($entity, null, $args->getEntityManager())
        );
    }
}

-----
<?php

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CustomEvent extends Event
{
    public const NAME = 'CustomeEvent';
}

class TestEventSubscriber
{
    private readonly EventDispatcherInterface $dispatcher;

    public function subscribe($entity)
    {
        global $args;
        $event = $this->dispatcher->dispatch(
            new CustomEvent($entity, null, $args->getEntityManager()),
            CustomEvent::NAME
        );
    }
}
