# Upgrade Toolkit Rector Rules Overview

## Categories

- [Oro 4.2](#oro-42)

- [Oro 5.1](#oro-51)

- [Oro 6.0](#oro-60)

- [Oro 6.1](#oro-61)

- [Namespace](#namespace)

<br>

## Oro 4.2

## MakeDispatchFirstArgumentEventRector

Makes the event object the first argument of the `dispatch()` method, with the event name as the second.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro42\MakeDispatchFirstArgumentEventRector`](../src/Rector/Rules/Oro42/MakeDispatchFirstArgumentEventRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro42\RootNodeTreeBuilderRector`](../src/Rector/Rules/Oro42/RootNodeTreeBuilderRector.php)

```diff
 use Symfony\Component\Config\Definition\Builder\TreeBuilder;

-$treeBuilder = new TreeBuilder();
-$rootNode = $treeBuilder->root('acme_root');
+$treeBuilder = new TreeBuilder('acme_root');
+$rootNode = $treeBuilder->getRootNode();
 $rootNode->someCall();
```

<br>

## Oro 5.1

## ClassConstantToStaticMethodCallRector

Replaces the class constant by the static method call

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector`](../src/Rector/Rules/Oro51/ClassConstantToStaticMethodCallRector.php)

```diff
-$this->send(\Acme\Bundle\DemoBundle\Async\Topics::SEND_EMAIL, []);
+$this->send(\Acme\Bundle\DemoBundle\Async\Topic\SendEmailTopic::getName(), []);
```

<br>

## ExtendedEntityUpdateRector

Updates extended entities to use a new trait and interface instead of a model class extend

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\ExtendedEntityUpdateRector`](../src/Rector/Rules/Oro51/ExtendedEntityUpdateRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\GenerateTopicClassesRector`](../src/Rector/Rules/Oro51/GenerateTopicClassesRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\ImplementCronCommandScheduleDefinitionInterfaceRector`](../src/Rector/Rules/Oro51/ImplementCronCommandScheduleDefinitionInterfaceRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\RenameValueNormalizerUtilMethodIfTrowsExceptionRector`](../src/Rector/Rules/Oro51/RenameValueNormalizerUtilMethodIfTrowsExceptionRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro51\TopicClassConstantUsageToTopicNameRector`](../src/Rector/Rules/Oro51/TopicClassConstantUsageToTopicNameRector.php)

```diff
-$this->send(\Acme\Bundle\DemoBundle\Async\Topics::SEND_EMAIL, []);
+$this->send(\Acme\Bundle\DemoBundle\Async\Topic\SendEmailTopic::getName(), []);
```

<br>

## Oro 6.0

## AddUserTypeCheckWhileAuthRector

Changes the implementation of anonymous_customer_user authentication in controllers. To check whether the user is authorized, it is not enough to check whether the user is absent in the token, it is also worth checking whether this user is not a customer visitor (CustomerVisitor::class).

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro60\AddUserTypeCheckWhileAuthRector`](../src/Rector/Rules/Oro60/AddUserTypeCheckWhileAuthRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\MinimizeAnnotationRector`](../src/Rector/Rules/Oro60/Annotation/MinimizeAnnotationRector.php)

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

## Oro 6.1

## ReplaceDynamicEnumClassInRepositoryFindRector

Refactors dynamic enum repository access by replacing class name resolution with ```EnumOption::class``` and ID generation using ```buildEnumOptionId()```

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumClassInRepositoryFindRector`](../src/Rector/Rules/Oro61/Enum/ReplaceDynamicEnumClassInRepositoryFindRector.php)

```diff
class SomeService
{
    public function doSomething()
    {
        $enumCode = 'some_enum';
        $id = 'some_id';

         $value = $this->doctrine
-            ->getRepository(\Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::buildEnumValueClassName($enumCode))
-            ->find($id);
+            ->getRepository(\Oro\Bundle\EntityExtendBundle\Entity\EnumOption::class)
+            ->find(\Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::buildEnumOptionId($enumCode, $id));
     }
 }
```

<br>

## ReplaceDynamicEnumFindAllWithEnumOptionFindByRector

Replaces dynamic enum class resolution in repository ```findAll()``` calls with direct usage of ```EnumOption::class``` and a ```findBy``` on enumCode

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumFindAllWithEnumOptionFindByRector`](../src/Rector/Rules/Oro61/Enum/ReplaceDynamicEnumFindAllWithEnumOptionFindByRector.php)

```diff
class SomeService
{
    public function doSomething()
    {
        $enumCode = 'some_enum';

        $value = $this->doctrine
-            ->getRepository(ExtendHelper::buildEnumValueClassName($enumCode))
-            ->findAll();
+            ->getRepository(\Oro\Bundle\EntityExtendBundle\Entity\EnumOption::class)->findBy(['enumCode' => $enumCode]);
    }
}
```

<br>

## ReplaceDynamicEnumClassInRepositoryFindByRector

Replaces dynamic enum repository calls using ```ExtendHelper::buildEnumValueClassName``` with static ```EnumOption::class``` references and rewrites search criteria by converting ```'name' => ...``` to ```'id' => ExtendHelper::buildEnumOptionId(...)```.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumClassInRepositoryFindByRector`](../src/Rector/Rules/Oro61/Enum/ReplaceDynamicEnumClassInRepositoryFindByRector.php)

```diff
class SomeService
{
    public function __construct(private ManagerRegistry $doctrine) {}

    public function doSomething()
    {
        $enumCode = 'some_enum';
        $name = 'tst_name';

         $value = $this->doctrine
-            ->getRepository(ExtendHelper::buildEnumValueClassName($enumCode))
-            ->findOneBy([
-                'priority' => 1,
-                'name' => $name,
-            ]);
+            ->getRepository(\Oro\Bundle\EntityExtendBundle\Entity\EnumOption::class)
+            ->findOneBy(['priority' => 1, 'id' => \Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::buildEnumOptionId($enumCode, $name)]);
    }
}
```

<br>

## AddGetDependenciesToEnumFixturesRector

Adds missing ```LoadLanguageData``` dependency to fixture classes that extends ```Doctrine\Common\DataFixtures\AbstractFixture``` and uses createEnumOption() or createEnumValue() calls.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\AddGetDependenciesToEnumFixturesRector`](../src/Rector/Rules/Oro61/Enum/AddGetDependenciesToEnumFixturesRector.php)

```diff
class SomeFixture extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $enumClass = ExtendHelper::buildEnumValueClassName($this->getEnumCode());
        $enumRepo = $manager->getRepository($enumClass);
        $enumValue = $enumRepo->createEnumValue($name, $index, false, strtolower($name));

        $manager->persist($enumValue);
        $manager->flush();
    }
+    /**
+     * It is required to ensure languages are loaded before enum options are created.
+     */
+    #[\Override]
+    public function getDependencies(): array
+    {
+        return [\Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData::class];
+    }
}
```

<br>

## Namespace

## RenameNamespaceRector

Replaces the old namespace with a new one.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector`](../src/Rector/Rules/Namespace/RenameNamespaceRector.php)

```diff
-$someObject = new SomeOldNamespace\SomeClass;
+$someObject = new SomeNewNamespace\SomeClass;
```

<br>
