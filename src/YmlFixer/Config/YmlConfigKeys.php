<?php

namespace Oro\UpgradeToolkit\YmlFixer\Config;

/**
 * Contains string keys that are used in the .yml configs
 */
final class YmlConfigKeys
{
    public const DATAGRIDS = 'datagrids';
    public const SOURCE = 'source';
    public const QUERY = 'query';
    public const JOIN = 'join';
    public const ALIAS = 'alias';
    public const SELECT = 'select';
    public const COLUMNS = 'columns';
    public const FILTERS = 'filters';
    public const SORTERS = 'sorters';
    public const FRONTEND_TYPE = 'frontend_type';
    public const DATA_NAME = 'data_name';
    public const CHOICES = 'choices';
    public const TRANSLATABLE_OPTIONS = 'translatable_options';
    public const TYPE = 'type';
    public const ENUM_CODE = 'enum_code';
    public const OPTIONS = 'options';
    public const ACL_RESOURCE = 'acl_resource';
    public const ENABLED = 'enabled';
    public const RENDERABLE = 'renderable';
    public const SKIP_ACL_CHECK = 'skip_acl_check';
    public const SKIP_ACL_APPLY = 'skip_acl_apply';

    public const SEARCH = 'search';
    public const TITLE_FIELDS = 'title_fields';

    public const SERVICES = 'services';
    public const ARGUMENTS = 'arguments';
    public const TAGS = 'tags';
    public const PRIORITY = 'priority';
    public const NAME = 'name';
    public const NAMESPACE = 'namespace';
    public const PARENT = 'parent';
    public const CALLS = 'calls';

    public const PROCESSES = 'processes';
    public const DEFINITIONS = 'definitions';
    public const CLASS_KEY = 'class';
    public const ACTIONS_CONFIG = 'actions_configuration';
    public const ACTIONS = 'actions';
    public const IDENTIFIER = 'identifier';
}
