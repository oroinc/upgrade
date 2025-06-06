<?php

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Doctrine\Persistence\ManagerRegistry;

class SomeService
{
    public function __construct(private ManagerRegistry $doctrine) {}

    public function doSomething()
    {
        $enumCode = 'some_enum';
        $name = 'tst_name';

        $value = $this->doctrine
            ->getRepository(ExtendHelper::buildEnumValueClassName($enumCode))
            ->findOneBy([
                'priority' => 1,
                'name' => $name,
            ]);
    }
}
-----
<?php

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Doctrine\Persistence\ManagerRegistry;

class SomeService
{
    public function __construct(private ManagerRegistry $doctrine) {}

    public function doSomething()
    {
        $enumCode = 'some_enum';
        $name = 'tst_name';

        $value = $this->doctrine
            ->getRepository(\Oro\Bundle\EntityExtendBundle\Entity\EnumOption::class)
            ->findOneBy(['priority' => 1, 'id' => \Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::buildEnumOptionId($enumCode, $name)]);
    }
}
