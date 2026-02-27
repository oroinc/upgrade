# Oro 7.0 Rector Rules

## ReplaceGetDefaultNameWithAttributeNameValueRector

Replaces deprecated ```Command::getDefaultName()``` usage with a literal string from the ```#[AsCommand]``` attribute.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\Console\ReplaceGetDefaultNameWithAttributeNameValueRector`](../../src/Rector/Rules/Oro70/Console/ReplaceGetDefaultNameWithAttributeNameValueRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\ParamConverter\AddressValidationActionParamConverterAttributeToMapEntityAttributeRector`](../../src/Rector/Rules/Oro70/FrameworkExtraBundle/ParamConverter/AddressValidationActionParamConverterAttributeToMapEntityAttributeRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\ParamConverter\OroParamConverterAttributeToMapEntityAttributeRector`](../../src/Rector/Rules/Oro70/FrameworkExtraBundle/ParamConverter/OroParamConverterAttributeToMapEntityAttributeRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeArrayToArgsRector`](../../src/Rector/Rules/Oro70/FrameworkExtraBundle/Template/TemplateAttributeArrayToArgsRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeTemplateArgumentRector`](../../src/Rector/Rules/Oro70/FrameworkExtraBundle/Template/TemplateAttributeTemplateArgumentRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeSetterToConstructorRector`](../../src/Rector/Rules/Oro70/FrameworkExtraBundle/Template/TemplateAttributeSetterToConstructorRector.php)

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

## AddTypeToSetParameterRector

Adds type hint to setParameter when entity object is passed.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\Doctrine\AddTypeToSetParameterRector`](../../src/Rector/Rules/Oro70/Doctrine/AddTypeToSetParameterRector.php)

```diff
-$queryBuilder->setParameter('product', $product);
+$queryBuilder->setParameter('product', $product->getId(), Types::INTEGER);
```

<br>

## ReplaceUseResultCacheRector

Replaces `useResultCache` method with `enableResultCache` or `disableResultCache` based on the first argument.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\Doctrine\ReplaceUseResultCacheRector`](../../src/Rector/Rules/Oro70/Doctrine/ReplaceUseResultCacheRector.php)

```diff
-$query->useResultCache(true, 3600, 'cache_key');
+$query->enableResultCache(3600, 'cache_key');

-$query->useResultCache(false, 3600, 'cache_key');
+$query->disableResultCache();
```

<br>

## ReplaceUuidGenerationStrategyRector

Replaces UUID generation strategy with CUSTOM and adds CustomIdGenerator attribute.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\Doctrine\ReplaceUuidGenerationStrategyRector`](../../src/Rector/Rules/Oro70/Doctrine/ReplaceUuidGenerationStrategyRector.php)

```diff
-#[ORM\GeneratedValue(strategy: 'UUID')]
+#[ORM\GeneratedValue(strategy: 'CUSTOM')]
+#[ORM\CustomIdGenerator(class: UuidGenerator::class)]
 public $id;
```

<br>

## AddFormWidgetAndHtml5OptionsRector

Adds widget and html5 options to form configureOptions method.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\Form\AddFormWidgetAndHtml5OptionsRector`](../../src/Rector/Rules/Oro70/Form/AddFormWidgetAndHtml5OptionsRector.php)

```diff
 public function configureOptions(OptionsResolver $resolver)
 {
-    $resolver->setDefaults(['input' => 'array']);
+    $resolver->setDefaults(['widget' => 'choice', 'html5' => false, 'input' => 'array']);
 }
```

<br>

## ReplaceReaderFactoryWithDirectInstantiationRector

Replaces ReaderFactory::createFromType with direct OpenSpout reader instantiation.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector`](../../src/Rector/Rules/Oro70/OpenSpout/ReplaceReaderFactoryWithDirectInstantiationRector.php)

```diff
-$reader = ReaderFactory::createFromType(Type::CSV);
+$reader = new CSVReader();

-$reader = ReaderFactory::createFromType(Type::XLSX);
+$reader = new XLSXReader();
```

<br>

## ReplaceWriterFactoryWithDirectInstantiationRector

Replaces WriterFactory::createFromType with direct OpenSpout writer instantiation.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector`](../../src/Rector/Rules/Oro70/OpenSpout/ReplaceWriterFactoryWithDirectInstantiationRector.php)

```diff
-$writer = WriterFactory::createFromType(Type::XLSX);
+$writer = new XLSXWriter();
```

<br>

## AddGetSupportedTypesMethodRector

Adds getSupportedTypes method to classes implementing serializer normalizer interfaces.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\Serializer\AddGetSupportedTypesMethodRector`](../../src/Rector/Rules/Oro70/Serializer/AddGetSupportedTypesMethodRector.php)

```diff
 class SomeNormalizer implements NormalizerInterface
 {
     public function normalize($object, $format = null, array $context = [])
     {
         return [];
     }
+
+    public function getSupportedTypes(?string $format): array
+    {
+        return ['object' => true];
+    }
 }
```

<br>

## MoveDefaultDescriptionToAsCommandAttributeRector

Moves $defaultDescription property value into existing #[AsCommand] attribute's description argument.

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro70\Console\MoveDefaultDescriptionToAsCommandAttributeRector`](../../src/Rector/Rules/Oro70/Console/MoveDefaultDescriptionToAsCommandAttributeRector.php)

```diff
-#[AsCommand(name: 'app:my-command')]
-class MyCommand extends Command {
-    protected static $defaultDescription = 'My description';
-}
+#[AsCommand(name: 'app:my-command', description: 'My description')]
+class MyCommand extends Command {
+}
```

<br>
