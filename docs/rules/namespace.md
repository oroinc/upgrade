# Namespace Rector Rules

## RenameNamespaceRector

Replaces the old namespace with a new one.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector`](../../src/Rector/Rules/Namespace/RenameNamespaceRector.php)

```diff
-$someObject = new SomeOldNamespace\SomeClass;
+$someObject = new SomeNewNamespace\SomeClass;
```
