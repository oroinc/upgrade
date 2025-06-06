<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

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
}
-----
<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

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
    /**
     * It is required to ensure languages are loaded before enum options are created.
     */
    #[\Override]
    public function getDependencies(): array
    {
        return [\Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData::class];
    }
}
