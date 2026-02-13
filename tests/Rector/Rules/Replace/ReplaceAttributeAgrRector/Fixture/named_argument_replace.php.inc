<?php

namespace App;

use App\Mocks\Route;

final class NamedArgumentReplace
{
    #[Route(name: 'old')]
    public function action(): void
    {
    }
}

-----
<?php

namespace App;

use App\Mocks\Route;

final class NamedArgumentReplace
{
    #[\App\Mocks\Route(name: 'new')]
    public function action(): void
    {
    }
}
