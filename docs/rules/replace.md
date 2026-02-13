# Replace Rector Rules

## ReplaceArgInMethodCallRector

Replaces a specific argument value in method/static calls with a new value.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Replace\ReplaceArgInMethodCallRector`](../../src/Rector/Rules/Replace/ReplaceArgInMethodCallRector.php)

```diff
 use Acme\Service\DataProcessor;

 class SomeClass
 {
     public function process()
     {
-        $processor->execute(mode: 'old');
+        $processor->execute(mode: 'new');
     }
 }
```

<br>

## ReplaceAttributeAgrRector

Replaces a configured attribute argument value for PHP 8 attributes.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Replace\ReplaceAttributeAgrRector`](../../src/Rector/Rules/Replace/ReplaceAttributeAgrRector.php)

```diff
-#[Route(name: 'old')]
+#[Route(name: 'new')]
 public function indexAction()
 {
     return [];
 }
```

<br>

## ReplacePropertyFetchWithConstructArgRector

Replaces property assignments after object instantiation with named constructor arguments.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Replace\ReplacePropertyFetchWithConstructArgRector`](../../src/Rector/Rules/Replace/ReplacePropertyFetchWithConstructArgRector.php)

```diff
-$query = new Query();
-$query->select = ['id', 'name'];
-$query->from = 'users';
+$query = new Query(select: ['id', 'name'], from: 'users');
```
