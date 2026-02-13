<?php

namespace App\Tests;

class Test
{
    function test()
    {
        $obj = new UnknownClass();
        $obj->prop1 = 'value1';
        $obj->prop2 = 'value2';

        return $obj;
    }
}
