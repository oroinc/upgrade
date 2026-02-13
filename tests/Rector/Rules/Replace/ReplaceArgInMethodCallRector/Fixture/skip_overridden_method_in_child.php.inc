<?php

namespace Test;

use App\Mocks\FooChild;

final class SomeClass
{
    public function run(FooChild $foo): void
    {
        $foo->bar('n', 'old');
    }
}
-----
<?php

namespace Test;

use App\Mocks\FooChild;

final class SomeClass
{
    public function run(FooChild $foo): void
    {
        $foo->bar('n', 'old');
    }
}
