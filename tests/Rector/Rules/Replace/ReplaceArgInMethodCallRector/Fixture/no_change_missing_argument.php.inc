<?php

namespace Test;

use App\Mocks\Foo;

final class SomeClass
{
    public function run(Foo $foo): void
    {
        // missing 2nd argument, should not be changed
        $foo->bar('n');
    }
}
-----
<?php

namespace Test;

use App\Mocks\Foo;

final class SomeClass
{
    public function run(Foo $foo): void
    {
        // missing 2nd argument, should not be changed
        $foo->bar('n');
    }
}
