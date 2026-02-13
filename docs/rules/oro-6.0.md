# Oro 6.0 Rector Rules

## AddUserTypeCheckWhileAuthRector

Changes the implementation of anonymous_customer_user authentication in controllers. To check whether the user is authorized, it is not enough to check whether the user is absent in the token, it is also worth checking whether this user is not a customer visitor (CustomerVisitor::class).

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro60\AddUserTypeCheckWhileAuthRector`](../../src/Rector/Rules/Oro60/AddUserTypeCheckWhileAuthRector.php)

```diff
-if ($this->getUser()) {
+if ($this->getUser() instanceof AbstractUser) {
     # implementation
 }
```

<br>

## MinimizeAnnotationRector

Minimizes multiline annotation into one line excluding whitespaces, tabs, etc

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\MinimizeAnnotationRector`](../../src/Rector/Rules/Oro60/Annotation/MinimizeAnnotationRector.php)

```diff
 /**
  * Represents some class.
  *
- * @Config(
- *      defaultValues={
- *          "dataaudit"={
- *              "auditable"=true
- *
- *          },
- *          "slug"={
- *              "source"="titles"
- *          }
- *     }
- * )
+ * @Config(defaultValues={"dataaudit"={"auditable"=true},"slug"={"source"="titles"}})
  */
 class SomeClass
 {
     /**
-    * @ConfigField(
-    *      defaultValues={
-    *          "dataaudit"={
-    *              "auditable"=true
-    *          }
-    *      }
-    * )
+    * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
     public function run()
     {
         return 'STRING';
     }
 }
```

<br>

## AnnotationTagRenameRector

Automates annotation tag renaming in PHP doc blocks using a configurable mapping.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\AnnotationTagRenameRector`](../../src/Rector/Rules/Oro60/Annotation/AnnotationTagRenameRector.php)

```diff
 /**
  * Some class description.
  *
- * @Doctrine\ORM\Mapping\Column(type="integer")
+ * @ORM\Column(type="integer")
  */
 class SomeClass
 {
     /**
-     * @Doctrine\ORM\Mapping\JoinColumn(name="user_id")
+     * @ORM\JoinColumn(name="user_id")
      */
     private $user;
 }
```

<br>

## SanitiseDocBlockRector

Sanitizes PHPDoc blocks by replacing specified characters or strings in comment lines, leaving annotation lines unchanged.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\SanitiseDocBlockRector`](../../src/Rector/Rules/Oro60/Annotation/SanitiseDocBlockRector.php)

```diff
 /**
- * This class handles "special" characters in comments.
- * It replaces & with and, © with (c), etc.
+ * This class handles special characters in comments.
+ * It replaces and with and, (c) with (c), etc.
  *
  * @Config(defaultValues={"dataaudit"={"auditable"=true}})
  */
 class SomeClass
 {
 }
```
