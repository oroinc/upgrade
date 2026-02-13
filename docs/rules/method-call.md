# Method Call Rector Rules

## OroMethodCallToPropertyFetchRector

Transforms method calls to property fetch or property assignment. When method has no arguments, converts to property fetch. When method has arguments, converts to property assignment.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\MethodCall\OroMethodCallToPropertyFetchRector`](../../src/Rector/Rules/MethodCall/OroMethodCallToPropertyFetchRector.php)

```diff
 use Oro\UpgradeToolkit\Tests\Template;

 class TestsClass
 {
     public function testMethod()
     {
         $template = new Template();

-        $templateValue = $template->getTemplate();
-        $template->setTemplate('some-template');
+        $templateValue = $template->template;
+        $template->template = 'some-template';
     }
 }
```

<br>

## RemoveReflectionSetAccessibleCallsRector

Remove Reflection::setAccessible() calls.

- class: [`Oro\UpgradeToolkit\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector`](../../src/Rector/Rules/MethodCall/RemoveReflectionSetAccessibleCallsRector.php)

```diff
 $reflectionProperty = new ReflectionProperty($object, 'property');
-$reflectionProperty->setAccessible(true);
 $value = $reflectionProperty->getValue($object);

 $reflectionMethod = new ReflectionMethod($object, 'method');
-$reflectionMethod->setAccessible(false);
 $reflectionMethod->invoke($object);
```
