<?php

namespace Test;

trait TraitWithMethod
{
    public function oldTraitMethod()
    {
        return 'old';
    }
}

-----
<?php

namespace Test;

trait TraitWithMethod
{
    public function newTraitMethod()
    {
        return 'old';
    }
}
