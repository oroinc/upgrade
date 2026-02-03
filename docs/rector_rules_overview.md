# Upgrade Toolkit Rector Rules Overview

## Categories

- [Oro 4.2](#oro-42)

- [Oro 5.1](#oro-51)

- [Oro 6.0](#oro-60)

- [Oro 6.1](#oro-61)

- [Oro 7.0](#oro-70)

- [Namespace](#namespace)

- [Renaming](#Renaming)

- [MethodCall](#methodcall)

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

## AnnotationTagRenameRector

Automates annotation tag renaming in PHP doc blocks using a configurable mapping.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\AnnotationTagRenameRector`](../src/Rector/Rules/Oro60/Annotation/AnnotationTagRenameRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\SanitiseDocBlockRector`](../src/Rector/Rules/Oro60/Annotation/SanitiseDocBlockRector.php)

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

## ReplaceExtendExtensionAwareTraitRector

Replaces the usage of `ExtendExtensionAwareTrait` with `OutdatedExtendExtensionAwareTrait` in migration classes implementing `Oro\Bundle\MigrationBundle\Migration\Migration`. Also removes related properties and methods.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceExtendExtensionAwareTraitRector`](../src/Rector/Rules/Oro61/Enum/ReplaceExtendExtensionAwareTraitRector.php)

```diff
 use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
+use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
 use Oro\Bundle\MigrationBundle\Migration\Migration;

 class SomeMigration implements Migration
 {
-    use ExtendExtensionAwareTrait;
-
-    private $extendExtension;
-
-    public function setExtendExtension(ExtendExtension $extendExtension)
-    {
-        $this->extendExtension = $extendExtension;
-    }
+    use OutdatedExtendExtensionAwareTrait;

     public function up(Schema $schema, QueryBag $queries)
     {
         // migration logic
     }
 }
```

<br>

## Oro 7.0

## ReplaceGetDefaultNameWithAttributeNameValueRector

Replaces deprecated ```Command::getDefaultName()``` usage with a literal string from the ```#[AsCommand]``` attribute.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\Console\ReplaceGetDefaultNameWithAttributeNameValueRector`](../src/Rector/Rules/Oro70/Console/ReplaceGetDefaultNameWithAttributeNameValueRector.php)

```diff
class SomeService
{
    public function getCommandName(UpgradeCommand $command): ?string
    {
-        if (UpgradeCommand::getDefaultName() === $command->getName()) {
-            return UpgradeCommand::getDefaultName();
+        if ('upgrade' === $command->getName()) {
+            return 'upgrade';

        return null;
    }
}
```

<br>

## AddressValidationActionParamConverterAttributeToMapEntityAttributeRector

Replaces ```#[ParamConverter]``` attributes with ```#[MapEntity]``` in addressValidationAction methods of AddressValidation controllers, converting them to method parameters.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\ParamConverter\AddressValidationActionParamConverterAttributeToMapEntityAttributeRector`](../src/Rector/Rules/Oro70/FrameworkExtraBundle/ParamConverter/AddressValidationActionParamConverterAttributeToMapEntityAttributeRector.php)

```diff
 use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
 use Oro\Bundle\CustomerBundle\Entity\Customer;

 class AddressValidationController extends AbstractAddressValidationController
 {
-    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["customerId" => "id"]])]
-    public function addressValidationAction(Request $request): Response|array
+    public function addressValidationAction(
+        Request $request,
+        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ["customerId" => "id"])]
+        \Oro\Bundle\CustomerBundle\Entity\Customer|null $customer = null
+    ): Response|array
     {
         return [];
     }
 }
```

<br>

## OroParamConverterAttributeToMapEntityAttributeRector

Replaces ```#[ParamConverter]``` attributes with ```#[MapEntity]``` in Oro platform controller methods.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\ParamConverter\OroParamConverterAttributeToMapEntityAttributeRector`](../src/Rector/Rules/Oro70/FrameworkExtraBundle/ParamConverter/OroParamConverterAttributeToMapEntityAttributeRector.php)

```diff
 use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

 class ProductController extends AbstractController
 {
-    #[ParamConverter('product', class: Product::class, options: ['mapping' => ['product_id' => 'id']])]
-    public function showAction(Product $product): array
+    public function showAction(
+        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ['product_id' => 'id'])]
+        Product $product
+    ): array
     {
         return [];
     }
 }
```

<br>

## TemplateAttributeArrayToArgsRector

Converts Template attribute constructor calls from array syntax to arguments syntax.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeArrayToArgsRector`](../src/Rector/Rules/Oro70/FrameworkExtraBundle/Template/TemplateAttributeArrayToArgsRector.php)

```diff
 use Symfony\Bridge\Twig\Attribute\Template;

 class TestController
 {
     public function indexAction()
     {
-        return new Template(['template' => '@TestBundle/Default/test.html.twig']);
+        return new Template('@TestBundle/Default/test.html.twig');
     }
 }
```

<br>

## TemplateAttributeTemplateArgumentRector

Adds template argument with generated template path to Template attributes that don't have explicit template specified.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeTemplateArgumentRector`](../src/Rector/Rules/Oro70/FrameworkExtraBundle/Template/TemplateAttributeTemplateArgumentRector.php)

```diff
 use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

 class ProductController extends AbstractController
 {
-    #[Template]
-    public function listAction(): array
+    #[Template('@OroProduct/Product/list.html.twig')]
+    public function listAction(): array
     {
         return [];
     }
 }
```

<br>

## TemplateAttributeSetterToConstructorRector

Converts Template attribute creation with setter method calls to constructor with arguments.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeSetterToConstructorRector`](../src/Rector/Rules/Oro70/FrameworkExtraBundle/Template/TemplateAttributeSetterToConstructorRector.php)

```diff
 use Symfony\Bridge\Twig\Attribute\Template;

 class TestController
 {
     public function indexAction()
     {
-        $template = new Template();
-        $template->setTemplate('@TestBundle/Default/test.html.twig');
-        return $template;
+        $template = new Template('@TestBundle/Default/test.html.twig');
+        return $template;
     }
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

## Renaming

## OroRenameClassRector

Replaces defined classes by new ones with the ability to specify target classes. Modified version of the standard RenameClassRector.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\Renaming\Name\OroRenameClassRector`](../src/Rector/Rules/Renaming/Name/OroRenameClassRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Renaming\PropertyFetch\OroRenamePropertyRector`](../src/Rector/Rules/Renaming/PropertyFetch/OroRenamePropertyRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Renaming\Attribute\AttributeNamedArgRenameRector`](../src/Rector/Rules/Renaming/Attribute/AttributeNamedArgRenameRector.php)

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

## MethodCall

## OroMethodCallToPropertyFetchRector

Transforms method calls to property fetch or property assignment. When method has no arguments, converts to property fetch. When method has arguments, converts to property assignment.

:wrench: **configure it!**

- class: [`Oro\UpgradeToolkit\Rector\Rules\MethodCall\OroMethodCallToPropertyFetchRector`](../src/Rector/Rules/MethodCall/OroMethodCallToPropertyFetchRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector`](../src/Rector/Rules/MethodCall/RemoveReflectionSetAccessibleCallsRector.php)

```diff
 $reflectionProperty = new ReflectionProperty($object, 'property');
-$reflectionProperty->setAccessible(true);
 $value = $reflectionProperty->getValue($object);

 $reflectionMethod = new ReflectionMethod($object, 'method');
-$reflectionMethod->setAccessible(false);
 $reflectionMethod->invoke($object);
```

<br>
