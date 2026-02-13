<?php

namespace App;

use App\Mocks\Route;

final class MultipleRulesDontLoseChange
{
    #[Route(name: 'old', methods: ['GET'])]
    public function action(): void
    {
    }
}

-----
<?php

namespace App;

use App\Mocks\Route;

final class MultipleRulesDontLoseChange
{
    #[\App\Mocks\Route(name: 'new', methods: ['POST'])]
    public function action(): void
    {
    }
}
