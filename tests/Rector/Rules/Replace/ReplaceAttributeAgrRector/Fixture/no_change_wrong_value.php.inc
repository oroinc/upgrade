<?php

namespace App;

use App\Mocks\Route;

final class NoChangeWrongValue
{
    #[Route(name: 'keep')]
    public function action(): void
    {
    }
}
