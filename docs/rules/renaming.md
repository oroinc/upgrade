# Renaming Rector Rules

## OroRenameClassRector

Replaces defined classes by new ones with the ability to specify target classes. Modified version of the standard RenameClassRector.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Renaming\Name\OroRenameClassRector`](../../src/Rector/Rules/Renaming/Name/OroRenameClassRector.php)

```diff
 use OldClass;

 class SomeClass
 {
-    public function run(OldClass $oldClass)
+    public function run(NewClass $newClass)
     {
-        return new OldClass();
+        return new NewClass();
     }
 }
```

<br>

## OroRenamePropertyRector

Replaces defined old properties by new ones with the ability to specify target classes. Modified version of the standard RenamePropertyRector.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Renaming\PropertyFetch\OroRenamePropertyRector`](../../src/Rector/Rules/Renaming/PropertyFetch/OroRenamePropertyRector.php)

```diff
 class SomeClass
 {
     public function run()
     {
-        return $this->oldProperty;
+        return $this->newProperty;
     }
 }
```

<br>

## AttributeNamedArgRenameRector

Renames named arguments in PHP attributes using a configurable mapping.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Renaming\Attribute\AttributeNamedArgRenameRector`](../../src/Rector/Rules/Renaming/Attribute/AttributeNamedArgRenameRector.php)

```diff
-#[SomeAttribute(old_arg_name: 'value')]
+#[SomeAttribute(newArgName: 'value')]
 class SomeClass
 {
-    #[SomeAttribute(old_arg_name: 'value')]
+    #[SomeAttribute(newArgName: 'value')]
     public function run()
     {
     }
 }
```

<br>

## OroRenameMethodRector

Renames method calls with the ability to specify target classes. Modified version of the standard RenameMethodRector with support for chained method calls.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Renaming\Method\OroRenameMethodRector`](../../src/Rector/Rules/Renaming/Method/OroRenameMethodRector.php)

```diff
 class SomeClass
 {
     public function run()
     {
-        return $this->oldMethod();
+        return $this->newMethod()->newChainedMethod();
     }
 }
```
