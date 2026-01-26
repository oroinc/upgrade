<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ExistingParameterController extends AbstractAddressValidationController
{
    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["id" => "id"]])]
    public function addressValidationAction(Request $request, ?Customer $customer = null): Response|array
    {
        return [];
    }
}

-----
<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ExistingParameterController extends AbstractAddressValidationController
{
    public function addressValidationAction(
        Request $request,
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ["id" => "id"])]
        ?Customer $customer = null
    ): Response|array
    {
        return [];
    }
}
