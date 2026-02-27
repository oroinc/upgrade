<?php

namespace App;

use App\Mocks\Route;

final class PositionalArgumentReplace
{
    #[Route('old_positional', ['GET'])]
    public function action(): void
    {
    }
}

-----
<?php

namespace App;

use App\Mocks\Route;

final class PositionalArgumentReplace
{
    #[Route('new_positional', ['POST'])]
    public function action(): void
    {
    }
}
