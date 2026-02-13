<?php

namespace Test;

use App\Mocks\Foo;

final class SomeClass
{
    public function run(Foo $foo): void
    {
        $foo->bar('n', 'something_else');
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
        $foo->bar('n', 'something_else');
    }
}
