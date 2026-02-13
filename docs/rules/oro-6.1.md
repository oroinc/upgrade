# Oro 6.1 Rector Rules

## ReplaceDynamicEnumClassInRepositoryFindRector

Refactors dynamic enum repository access by replacing class name resolution with ```EnumOption::class``` and ID generation using ```buildEnumOptionId()```

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumClassInRepositoryFindRector`](../../src/Rector/Rules/Oro61/Enum/ReplaceDynamicEnumClassInRepositoryFindRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumFindAllWithEnumOptionFindByRector`](../../src/Rector/Rules/Oro61/Enum/ReplaceDynamicEnumFindAllWithEnumOptionFindByRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumClassInRepositoryFindByRector`](../../src/Rector/Rules/Oro61/Enum/ReplaceDynamicEnumClassInRepositoryFindByRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\AddGetDependenciesToEnumFixturesRector`](../../src/Rector/Rules/Oro61/Enum/AddGetDependenciesToEnumFixturesRector.php)

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

- class: [`Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceExtendExtensionAwareTraitRector`](../../src/Rector/Rules/Oro61/Enum/ReplaceExtendExtensionAwareTraitRector.php)

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
