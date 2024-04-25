<?php

$this->send(\Oro\Tests\Rector\TopicClassConstantUsageToTopicNameRector\Mocks\Async\Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, []);
$this->send(\Oro\Tests\Rector\TopicClassConstantUsageToTopicNameRector\Mocks\Async\Topics::JOB_GENERATE_DIRECT_URL_FOR_ENTITIES, []);
$this->send(\Oro\Tests\Rector\TopicClassConstantUsageToTopicNameRector\Mocks\Async\Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE, []);
$this->send(\Oro\Tests\Rector\TopicClassConstantUsageToTopicNameRector\Mocks\Async\Topics::REMOVE_DIRECT_URL_FOR_ENTITY_TYPE, []);

?>
-----
<?php

$this->send(\Oro\Tests\Rector\TopicClassConstantUsageToTopicNameRector\Mocks\Async\Topic\GenerateDirectUrlForEntitiesTopic::getName(), []);
$this->send(\Oro\Tests\Rector\TopicClassConstantUsageToTopicNameRector\Mocks\Async\Topics::JOB_GENERATE_DIRECT_URL_FOR_ENTITIES, []);
$this->send(\Oro\Tests\Rector\TopicClassConstantUsageToTopicNameRector\Mocks\Async\Topic\RegenerateDirectUrlForEntityTypeTopic::getName(), []);
$this->send(\Oro\Tests\Rector\TopicClassConstantUsageToTopicNameRector\Mocks\Async\Topic\RemoveDirectUrlForEntityTypeTopic::getName(), []);

?>
