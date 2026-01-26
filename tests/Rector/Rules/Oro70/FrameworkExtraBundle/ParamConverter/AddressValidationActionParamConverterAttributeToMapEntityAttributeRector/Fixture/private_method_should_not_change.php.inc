<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Some\Entity\Customer;
use Symfony\Component\HttpFoundation\Request;

class PrivateMethodController extends AbstractAddressValidationController
{
    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["id" => "id"]])]
    private function addressValidationAction(Request $request): array
    {
        return [];
    }
}

-----
<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Some\Entity\Customer;
use Symfony\Component\HttpFoundation\Request;

class PrivateMethodController extends AbstractAddressValidationController
{
    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["id" => "id"]])]
    private function addressValidationAction(Request $request): array
    {
        return [];
    }
}

