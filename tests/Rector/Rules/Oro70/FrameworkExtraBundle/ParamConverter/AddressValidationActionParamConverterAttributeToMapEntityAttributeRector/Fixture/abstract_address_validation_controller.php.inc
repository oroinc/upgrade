<?php

namespace Oro\Bundle\AddressValidationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class AddressController extends AbstractAddressValidationController
{
    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["customerId" => "id"]])]
    public function addressValidationAction(Request $request): Response|array
    {
        return $this->render('template.html.twig');
    }
}

-----
<?php

namespace Oro\Bundle\AddressValidationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class AddressController extends AbstractAddressValidationController
{
    public function addressValidationAction(
        Request $request,
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ["customerId" => "id"])]
        \Oro\Bundle\CustomerBundle\Entity\Customer|null $customer = null
    ): Response|array
    {
        return $this->render('template.html.twig');
    }
}
