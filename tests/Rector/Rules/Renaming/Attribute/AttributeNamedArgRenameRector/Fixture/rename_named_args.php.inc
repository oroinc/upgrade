<?php
namespace Test;

use Oro\Bundle\SecurityBundle\Attribute\Acl;

class TestController
{
    #[Acl(
        id: 'oro_customer_view',
        type: 'entity',
        permission: 'VIEW',
        group_name: 'commerce'
    )]
    public function viewAction()
    {
    }

    #[Acl('oro_customer_update', group_name: 'commerce')]
    public function updateAction()
    {
    }

    #[AclAncestor(acl_annotation_id: 'oro_customer_view')]
    public function listAction()
    {
    }
}
-----
<?php
namespace Test;

use Oro\Bundle\SecurityBundle\Attribute\Acl;

class TestController
{
    #[\Oro\Bundle\SecurityBundle\Attribute\Acl(id: 'oro_customer_view', type: 'entity', permission: 'VIEW', groupName: 'commerce')]
    public function viewAction()
    {
    }

    #[\Oro\Bundle\SecurityBundle\Attribute\Acl('oro_customer_update', groupName: 'commerce')]
    public function updateAction()
    {
    }

    #[AclAncestor(value: 'oro_customer_view')]
    public function listAction()
    {
    }
}
