# Oro 5.1 Rector Rules

## ClassConstantToStaticMethodCallRector

Replaces the class constant by the static method call

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector`](../../src/Rector/Rules/Oro51/ClassConstantToStaticMethodCallRector.php)

```diff
-$this->send(\Acme\Bundle\DemoBundle\Async\Topics::SEND_EMAIL, []);
+$this->send(\Acme\Bundle\DemoBundle\Async\Topic\SendEmailTopic::getName(), []);
```

<br>

## ExtendedEntityUpdateRector

Updates extended entities to use a new trait and interface instead of a model class extend

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\ExtendedEntityUpdateRector`](../../src/Rector/Rules/Oro51/ExtendedEntityUpdateRector.php)

```diff
 namespace App\Entity;

 use App\Model\ExtendFoo;

-class Foo extends ExtendFoo
+class Foo implements \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface
 {
+    use \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
     public function __construct($bar)
     {
         $this->bar = $bar;
-        parent::__construct();
     }
 }
```

<br>

## GenerateTopicClassesRector

Generates topic classes

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\GenerateTopicClassesRector`](../../src/Rector/Rules/Oro51/GenerateTopicClassesRector.php)

```diff
+// new file: "app/Async/FirstTopic.php"
 namespace App\Async\Topics;

-final class Topics
+final class FirstTopic extends \Oro\Component\MessageQueue\Topic\AbstractTopic
 {
-    public const FIRST = 'first';
-    public const SECOND = 'second';
+    public static function getName(): string
+    {
+        return 'first';
+    }
+    public static function getDescription(): string
+    {
+        // TODO: Implement getDescription() method.
+        return '';
+    }
+    public function configureMessageBody(\Symfony\Component\OptionsResolver\OptionsResolver $resolver): void
+    {
+        // TODO: Implement configureMessageBody() method.
+    }
+}
+
+// new file: "app/Async/SecondTopic.php"
+namespace App\Async\Topics;
+
+final class SecondTopic extends \Oro\Component\MessageQueue\Topic\AbstractTopic
+{
+    public static function getName(): string
+    {
+        return 'second';
+    }
+    public static function getDescription(): string
+    {
+        // TODO: Implement getDescription() method.
+        return '';
+    }
+    public function configureMessageBody(\Symfony\Component\OptionsResolver\OptionsResolver $resolver): void
+    {
+        // TODO: Implement configureMessageBody() method.
+    }
 }
```

<br>

## ImplementCronCommandScheduleDefinitionInterfaceRector

Adds the `CronCommandScheduleDefinitionInterface` interface to the Command::class implementations with the `getDefaultDefinition()` method

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\ImplementCronCommandScheduleDefinitionInterfaceRector`](../../src/Rector/Rules/Oro51/ImplementCronCommandScheduleDefinitionInterfaceRector.php)

```diff
-class SomeClass extends \Symfony\Component\Console\Command\Command
+class SomeClass extends \Symfony\Component\Console\Command\Command implements \Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface
 {
-    public function getDefaultDefinition()
+    public function getDefaultDefinition(): string
     {
         return '* * * * * ? *';
     }
 }
```

<br>

## RenameValueNormalizerUtilMethodIfTrowsExceptionRector

Turns method names into new ones.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\RenameValueNormalizerUtilMethodIfTrowsExceptionRector`](../../src/Rector/Rules/Oro51/RenameValueNormalizerUtilMethodIfTrowsExceptionRector.php)

```diff
 Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
-                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType, true);
-                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType, false);
+                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
+                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::tryConvertToEntityType($valueNormalizer, $entityType);

-                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType);
-                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType, true);
-                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType, false);
+                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
+                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
+                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::tryConvertToEntityClass($valueNormalizer, $entityType);
```

<br>

## TopicClassConstantUsageToTopicNameRector

Replaces class Topics constant reference with the `*Topic::getName()` call

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\TopicClassConstantUsageToTopicNameRector`](../../src/Rector/Rules/Oro51/TopicClassConstantUsageToTopicNameRector.php)

```diff
-$this->send(\Acme\Bundle\DemoBundle\Async\Topics::SEND_EMAIL, []);
+$this->send(\Acme\Bundle\DemoBundle\Async\Topic\SendEmailTopic::getName(), []);
```
