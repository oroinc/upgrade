<?php

namespace Test;

class StaticService
{
    public static function getInstance()
    {
        return new self();
    }

    public static function create()
    {
        return new self();
    }

    public function initialize()
    {
        return $this;
    }
}

class TestClass
{
    public function testMethod()
    {
        $instance = StaticService::getInstance();
        return $instance;
    }

    public function anotherMethod()
    {
        return StaticService::getInstance();
    }
}

-----
<?php

namespace Test;

class StaticService
{
    public static function getInstance()
    {
        return new self();
    }

    public static function create()
    {
        return new self();
    }

    public function initialize()
    {
        return $this;
    }
}

class TestClass
{
    public function testMethod()
    {
        $instance = StaticService::create()->initialize();
        return $instance;
    }

    public function anotherMethod()
    {
        return StaticService::create()->initialize();
    }
}
