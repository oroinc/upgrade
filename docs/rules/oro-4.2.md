# Oro 4.2 Rector Rules

## MakeDispatchFirstArgumentEventRector

Makes the event object the first argument of the `dispatch()` method, with the event name as the second.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro42\MakeDispatchFirstArgumentEventRector`](../../src/Rector/Rules/Oro42/MakeDispatchFirstArgumentEventRector.php)

```diff
 use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

 class SomeClass
 {
     public function run(EventDispatcherInterface $eventDispatcher)
     {
-        $eventDispatcher->dispatch('event_name', new Event());
+        $eventDispatcher->dispatch(new Event(), 'event_name');
     }
 }
```

<br>

## RootNodeTreeBuilderRector

Changes TreeBuilder with the `root()` call to the constructor passed root and the `getRootNode()` call

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro42\RootNodeTreeBuilderRector`](../../src/Rector/Rules/Oro42/RootNodeTreeBuilderRector.php)

```diff
 use Symfony\Component\Config\Definition\Builder\TreeBuilder;

-$treeBuilder = new TreeBuilder();
-$rootNode = $treeBuilder->root('acme_root');
+$treeBuilder = new TreeBuilder('acme_root');
+$rootNode = $treeBuilder->getRootNode();
 $rootNode->someCall();
```
