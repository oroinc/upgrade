<?php

namespace Oro\UpgradeToolkit\Tests\Rector\ExtendedEntityUpdateRector\Fixture\Entity;

use Oro\UpgradeToolkit\Tests\Rector\ExtendedEntityUpdateRector\Fixture\Bundle\Model\ExtendFoo;

class Foo extends ExtendFoo
{
    public function __construct($bar)
    {
        $this->bar = $bar;
        parent::__construct();
    }
}

?>
-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\ExtendedEntityUpdateRector\Fixture\Entity;

use Oro\UpgradeToolkit\Tests\Rector\ExtendedEntityUpdateRector\Fixture\Bundle\Model\ExtendFoo;

class Foo implements \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface
{
    use \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
    public function __construct($bar)
    {
        $this->bar = $bar;
    }
}

?>
