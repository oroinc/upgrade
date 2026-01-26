<?php

namespace Oro\Bundle\AddressValidationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends AbstractAddressValidationController
{
    #[ParamConverter("customer", class: Customer::class, expr: "repository.findInSomeSpecialWayById(id)")]
    public function addressValidationAction(Request $request): Response|array
    {
        return $this->render('order/validation.html.twig');
    }

    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["id" => "id"]])]
    public function regularAction()
    {
        return [];
    }
}

-----
<?php

namespace Oro\Bundle\AddressValidationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends AbstractAddressValidationController
{
    public function addressValidationAction(
        Request $request,
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(expr: "repository.findInSomeSpecialWayById(id)")]
        \Oro\Bundle\CustomerBundle\Entity\Customer|null $customer = null
    ): Response|array
    {
        return $this->render('order/validation.html.twig');
    }

    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["id" => "id"]])]
    public function regularAction()
    {
        return [];
    }
}
