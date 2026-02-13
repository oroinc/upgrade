<?php

namespace Test;

use App\Mocks\Foo;

final class SomeClass
{
    public function run(): void
    {
        Foo::staticBar('old');
    }
}
-----
<?php

namespace Test;

use App\Mocks\Foo;

final class SomeClass
{
    public function run(): void
    {
        Foo::staticBar('new');
    }
}
