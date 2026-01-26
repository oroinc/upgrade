<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ComplexMappingController extends AbstractAddressValidationController
{
    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["customerId" => "id", "slug" => "slug"]])]
    public function addressValidationAction(Request $request): Response|array
    {
        $data = $request->query->all();
        return $this->json($data);
    }
}

-----
<?php

namespace Oro\Bundle\AddressValidationBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ComplexMappingController extends AbstractAddressValidationController
{
    public function addressValidationAction(
        Request $request,
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ["customerId" => "id", "slug" => "slug"])]
        \Oro\Bundle\CustomerBundle\Entity\Customer|null $customer = null
    ): Response|array
    {
        $data = $request->query->all();
        return $this->json($data);
    }
}
