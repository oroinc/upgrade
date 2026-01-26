<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class MultipleParamConverterController extends AbstractAddressValidationController
{
    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["customerId" => "id"]])]
    #[ParamConverter("address", class: CustomerAddress::class, options: ["mapping" => ["addressId" => "id"]])]
    public function addressValidationAction(Request $request): Response|array
    {
        return [];
    }
}

-----
<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class MultipleParamConverterController extends AbstractAddressValidationController
{
    public function addressValidationAction(
        Request $request,
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ["customerId" => "id"])]
        \Oro\Bundle\CustomerBundle\Entity\Customer|null $customer = null,
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ["addressId" => "id"])]
        \Oro\Bundle\CustomerBundle\Entity\CustomerAddress|null $address = null
    ): Response|array
    {
        return [];
    }
}

