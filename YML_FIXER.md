# YmlFixer

YmlFixer is a command in the `upgrade-toolkit` that applies predefined rules to YAML files located in the processed directory. 
Each rule is defined in a separate class and applied sequentially.

The command runs automatically when php bin/upgrade-toolkit is executed but can also be run independently if needed:

```bash
php bin/upgrade-toolkit yml:fix
```

However, in most cases, you will not need to run it separately.

Rule Creation
------------

This overview explains how to create a basic YmlFixer rule by demonstrating the process of replacing one service argument with another.

1. Create a class that implements the YmlFixerInterface

```php
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

class ExampleFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        // The rule code
    }

    #[\Override]
    public function matchFile(): string
    {
        // Returns file path glob pattern the rule should be applied to
    }
}
```

2. Define the glob pattern of the files that should be processed

```php
...
    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/services.yml';
    }
...
```

3. Implement the rule Logic

> [!TIP]
>
> The $config parameter in the fix method represents the array definition of the currently processed YAML file.

Below is an example of how to implement the logic to replace a service argument:

```php
use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
...
    #[\Override]
    public function fix(array &$config): void
    {
        foreach ($config[Keys::SERVICES] as $serviceName => $serviceDef) {
            foreach ($serviceDef[Keys::ARGUMENTS] as $key => $argument) {
                if ('@some_OLD_argument' === $argument)) {
                    $config[Keys::SERVICES][$serviceName][Keys::ARGUMENTS][$key] = '@some_NEW_argument';
                }
            }
        }
    }
...
```

> [!TIP]
> 
> `Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys` contains most keys used in Oro YAML configurations.

4. Add the rule to the configuration

Add the newly created rule to the config file located in the `upgrade-toolkit/src/YmlFixer/Config/Config.php` file:

```php
...
   public function getRules(): array
       {
           return [
               ...
               ExampleFixer::class,
           ];
       }
...
```

Below is the full code for `ExampleFixer`:

```php
use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

class ExampleFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        foreach ($config[Keys::SERVICES] as $serviceName => $serviceDef) {
            foreach ($serviceDef[Keys::ARGUMENTS] as $key => $argument) {
                if ('@some_OLD_argument' === $argument)) {
                    $config[Keys::SERVICES][$serviceName][Keys::ARGUMENTS][$key] = '@some_NEW_argument';
                }
            }
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/services.yml';
    }
}
```

Tests
-----

It is strongly advised to create a test for newly added rules. 
Below is an example of a test that could be written for the `ExampleFixer` rule.

```php
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class ExampleFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            ExampleFixer::class,
            'path/to/fixture/before/the/rule/was/applied/services.yml',
            'path/to/fixture/after/the/rule/was/applied/replaced_args_services.yml'
        );
    }
}
```

AbstractYmlFixerTestCase provides the method `testRule` which requires: 
* The class name of the tested rule
* Fixture files that contain the yml configs before and after the rule is applied


Advanced
--------

In some cases, it may be necessary to add rules that share similar logic and can be grouped together.

For example, renaming several services may be required. 

This can be accomplished by adding configuration.

The simplest way is to add the `__construct` and the `config` methods to the rule code.

```php
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

class ExampleFixer implements YmlFixerInterface
{
    public function __construct(
        private ?array $ruleConfiguration = null,
    ) {
    }

    #[\Override]
    public function fix(array &$config): void
    {
        // The rule code
    }

    #[\Override]
    public function matchFile(): string
    {
        // Returns file path glob pattern the rule should be applied to
    }

    private function config(): array
    {
        return $this->ruleConfiguration ?? [
            // Needed Configuration
        ];
    }
}
```

The test should be structured as follows:

```php
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class ExampleFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $ruleConfiguration = [
            // Test Configuration
        ];
        $this->testRule(
            ExampleFixer::class,
            'path/to/fixture/before/the/rule/was/applied/services.yml',
            'path/to/fixture/after/the/rule/was/applied/replaced_args_services.yml',
            $ruleConfiguration
        );
    }
}
```

Complete examples of a rule with configuration and corresponding tests can be found in:
* `Oro\UpgradeToolkit\YmlFixer\Rules\Services\RenameServiceFixer`
* `Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Services\RenameServiceFixerTest`
