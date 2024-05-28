<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro60\AddUserTypeCheckWhileAuthRector\Fixture;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MyController extends AbstractController
{
    public function myControllerMethod(): void
    {
        if ($this->getUser()) {}
        if (null !== $this->getUser()) {}
        if ($this->getUser() !== null) {}
        if (null != $this->getUser()) {}
        if ($this->getUser() != null) {}
        if (!is_null($this->getUser())) {}

        if (!$this->getUser()) {}
        if (null === $this->getUser()) {}
        if ($this->getUser() === null) {}
        if (null == $this->getUser()) {}
        if ($this->getUser() == null) {}
        if (is_null($this->getUser())) {}
    }
}

?>
-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro60\AddUserTypeCheckWhileAuthRector\Fixture;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MyController extends AbstractController
{
    public function myControllerMethod(): void
    {
        if ($this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if ($this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if ($this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if ($this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if ($this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if ($this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}

        if (!$this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if (!$this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if (!$this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if (!$this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if (!$this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
        if (!$this->getUser() instanceof \Oro\Bundle\UserBundle\Entity\AbstractUser) {}
    }
}

?>
