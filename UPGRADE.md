PHP-SDK UPGRADE
===============

To v1.18.0
----------

* Methods `Blackfire\LoopClient::promoteReferenceSignal`, `Blackfire\LoopClient::attachReference`,
  `Blackfire\Profile\Configuration::getReference`, `Blackfire\Profile\Configuration::setReference`,
  `Blackfire\Profile\Configuration::isNewReference`, `Blackfire\Profile\Configuration::setAsReference`
  are deprecated.
* Class `\Blackfire\Exception\ReferenceNotFoundException` is deprecated.

To v1.16.0
----------

* Method `getReport($scenarioUuid)` is deprecated. Use `getScenarioReport($scenarioUuid)` instead,
  or `getBuildReport($buildUuid)` for a full Build Report.
* Method `closeBuild()` return was void. It now returns a `Blackfire\Report`.

To v1.14.0
----------

Before this release, a build was only one scenario with one report.
Now a build can have many scenarios.

Changes are:
* deprecate class `\Blackfire\Build` (replaced by `\Blackfire\Build\Scenario`)
* deprecate methods `createBuild`, `endBuild` and `addJobInBuild` (replaced by `startScenario`, `closeScenario` and `addJobInScenario`)
* add new classes `\Blackfire\Build\Build` and `\Blackfire\Build\Scenario`
* add new methods `startBuild`, `closeBuild`, `startScenario`, `closeScenario` and `addJobInScenario`

The previous code:

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

$blackfire = new \Blackfire\Client();

// create a build
$build = $blackfire->createBuild('Blackfire dev', array(
    'title' => 'Legacy build',
    'trigger_name' => 'PHP',
    'external_id' => 'c:my-scenario',
    'external_parent_id' => 'b:my-scenario',
));

// create a configuration
$config = new \Blackfire\Profile\Configuration();
$config->setBuild($build);

// create as many profiles as you need
$probe = $blackfire->createProbe($config);

// some PHP code you want to profile
echo strlen('Hello !');

$blackfire->endProbe($probe);

// end the build and fetch the report
$report = $blackfire->endBuild($build);
```

should now be written:

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

$blackfire = new \Blackfire\Client();

// create a build
$build = $blackfire->startBuild('Blackfire dev', array(
    'title' => 'My build',
    'trigger_name' => 'PHP',
));

// create a scenario
$scenario = $blackfire->startScenario($build, array(
    'title' => 'Test documentation',
    'external_id' => 'c:my-scenario',
    'external_parent_id' => 'b:my-scenario',
));

// create a configuration
$config = new \Blackfire\Profile\Configuration();
$config->setScenario($scenario);

// create as many profiles as you need
$probe = $blackfire->createProbe($config);

// some PHP code you want to profile
echo strlen('Hello !');

$blackfire->endProbe($probe);

// end the build and fetch the report
$report = $blackfire->closeScenario($scenario);

$blackfire->closeBuild($build);
```
