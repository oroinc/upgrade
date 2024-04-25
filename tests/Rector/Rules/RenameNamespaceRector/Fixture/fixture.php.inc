<?php

use Old\Abstract\Controller\Namespace\AbstractTestController;

class TestController extends AbstractTestController
{
}
-----
<?php

use New\Abstract\Controller\Namespace\AbstractTestController;

class TestController extends \New\Abstract\Controller\Namespace\AbstractTestController
{
}
