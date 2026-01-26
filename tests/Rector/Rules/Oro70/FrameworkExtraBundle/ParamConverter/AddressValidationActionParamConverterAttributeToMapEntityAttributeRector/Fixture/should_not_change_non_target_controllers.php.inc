<?php

namespace Some\Other\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class RegularController
{
    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["id" => "id"]])]
    public function addressValidationAction(): array
    {
        return [];
    }
}

-----
<?php

namespace Some\Other\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class RegularController
{
    #[ParamConverter("customer", class: Customer::class, options: ["mapping" => ["id" => "id"]])]
    public function addressValidationAction(): array
    {
        return [];
    }
}
