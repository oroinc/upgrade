<?php

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rootNode_name');
    }
}

-----
<?php

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('rootNode_name');
        $rootNode = $treeBuilder->getRootNode();
    }
}
