<?php

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Doctrine\Persistence\ManagerRegistry;

class SomeService
{
    public function __construct(private ManagerRegistry $doctrine) {}

    public function doSomething()
    {
        $enumCode = 'some_enum';

        $value = $this->doctrine
            ->getRepository(ExtendHelper::buildEnumValueClassName($enumCode))
            ->findAll();
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

        $value = $this->doctrine
            ->getRepository(\Oro\Bundle\EntityExtendBundle\Entity\EnumOption::class)->findBy(['enumCode' => $enumCode]);
    }
}
