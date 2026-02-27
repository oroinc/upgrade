<?php

namespace Oro\UpgradeToolkit\Configuration;

/**
 * Rector command`s names
 */
enum RectorCommands: string
{
    case CUSTOM_RULE = 'custom-rule';
    case LIST_RULES = 'list-rules';
    case PROCESS = 'process';
    case SETUP_CI = 'setup-ci';
}
