<?php

namespace App\Tests;

use App\Mocks\TestClassAutoDetect;

class Test
{
    public function test()
    {
        $obj = new TestClassAutoDetect();
        $obj->first = 'value1';
        $obj->second = 'value2';
        $obj->third = 'value3';

        return $obj;
    }
}

-----
<?php

namespace App\Tests;

use App\Mocks\TestClassAutoDetect;

class Test
{
    public function test()
    {
        $obj = new TestClassAutoDetect(first: 'value1', second: 'value2', third: 'value3');

        return $obj;
    }
}
