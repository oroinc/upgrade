<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class MethodWithoutParamConverterController extends AbstractAddressValidationController
{
    public function addressValidationAction(Request $request): Response|array
    {
        return [];
    }

    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["id" => "id"]])]
    public function someOtherAction(Request $request): array
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

class MethodWithoutParamConverterController extends AbstractAddressValidationController
{
    public function addressValidationAction(Request $request): Response|array
    {
        return [];
    }

    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["id" => "id"]])]
    public function someOtherAction(Request $request): array
    {
        return [];
    }
}
