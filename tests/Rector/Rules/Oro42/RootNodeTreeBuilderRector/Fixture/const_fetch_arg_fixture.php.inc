<?php

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(Extension::ROOT_ALIAS);
    }
}

-----
<?php

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(Extension::ROOT_ALIAS);
        $rootNode = $treeBuilder->getRootNode();
    }
}
