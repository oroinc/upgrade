<?php

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($titleStorage->getRootNodeName());
    }
}

-----
<?php

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder($titleStorage->getRootNodeName());
        $rootNode = $treeBuilder->getRootNode();
    }
}
