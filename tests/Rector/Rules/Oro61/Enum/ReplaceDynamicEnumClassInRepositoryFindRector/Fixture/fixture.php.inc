<?php

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Doctrine\Persistence\ManagerRegistry;

class SomeService
{
    public function __construct(private ManagerRegistry $doctrine) {}

    public function doSomething()
    {
        $enumCode = 'some_enum';
        $id = 'some_id';

        $value = $this->doctrine
            ->getRepository(ExtendHelper::buildEnumValueClassName($enumCode))
            ->find($id);
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
        $id = 'some_id';

        $value = $this->doctrine
            ->getRepository(\Oro\Bundle\EntityExtendBundle\Entity\EnumOption::class)
            ->find(\Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::buildEnumOptionId($enumCode, $id));
    }
}
