<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class EntityAttributeController extends AbstractAddressValidationController
{
    #[Entity("customer", class: Customer::class, expr: "repository.findOneBy({'id': id})")]
    public function addressValidationAction(Request $request): Response|array
    {
        return [];
    }
}

-----
<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class EntityAttributeController extends AbstractAddressValidationController
{
    public function addressValidationAction(
        Request $request,
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(expr: "repository.findOneBy({'id': id})")]
        \Oro\Bundle\CustomerBundle\Entity\Customer|null $customer = null
    ): Response|array
    {
        return [];
    }
}
