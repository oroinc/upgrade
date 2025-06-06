<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class SomeFixture extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $enumRepo = $manager->getRepository(EnumOption::class);
        $enumValue = $enumRepo->createEnumOption($this->getEnumCode(), strtolower($name), $name, $index);

        $manager->persist($enumValue);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LoadUserRolesData::class];
    }
}
-----
<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class SomeFixture extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $enumRepo = $manager->getRepository(EnumOption::class);
        $enumValue = $enumRepo->createEnumOption($this->getEnumCode(), strtolower($name), $name, $index);

        $manager->persist($enumValue);
        $manager->flush();
    }

    /**
     * Adds required dependency to ensure enum options are created after loading languages.
     */
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUserRolesData::class, \Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData::class];
    }
}
