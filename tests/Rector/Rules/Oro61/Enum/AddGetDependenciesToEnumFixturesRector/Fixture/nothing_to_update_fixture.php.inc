<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData;

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

    public function getDependencies(): array
    {
        return [LoadLanguageData::class];
    }
}
-----
<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData;

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

    public function getDependencies(): array
    {
        return [LoadLanguageData::class];
    }
}
