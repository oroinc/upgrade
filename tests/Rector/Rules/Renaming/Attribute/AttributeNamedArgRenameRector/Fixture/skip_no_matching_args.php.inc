<?php
namespace Test;

use Oro\Bundle\SecurityBundle\Attribute\Acl;

class NoChangesController
{
    #[Acl(id: 'oro_customer_view', type: 'entity', permission: 'VIEW')]
    public function viewAction()
    {
    }
    #[Acl('oro_customer_create')]
    public function createAction()
    {
    }
}
-----
<?php
namespace Test;

use Oro\Bundle\SecurityBundle\Attribute\Acl;

class NoChangesController
{
    #[Acl(id: 'oro_customer_view', type: 'entity', permission: 'VIEW')]
    public function viewAction()
    {
    }
    #[Acl('oro_customer_create')]
    public function createAction()
    {
    }
}
