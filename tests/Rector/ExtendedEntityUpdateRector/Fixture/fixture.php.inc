<?php

namespace Oro\Tests\Rector\ExtendedEntityUpdateRector\Fixture\Entity;

use Oro\Tests\Rector\ExtendedEntityUpdateRector\Fixture\Bundle\Model\ExtendFoo;

class Foo extends ExtendFoo
{
}

?>
-----
<?php

namespace Oro\Tests\Rector\ExtendedEntityUpdateRector\Fixture\Entity;

use Oro\Tests\Rector\ExtendedEntityUpdateRector\Fixture\Bundle\Model\ExtendFoo;

class Foo implements \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface
{
    use \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
}

?>
