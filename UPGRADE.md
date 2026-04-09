PHP-SDK UPGRADE
===============

To v2.6.0
---------

The entire **Build** feature is deprecated and will be removed in 3.0.
Migrate to [Blackfire Player](https://docs.blackfire.io/builds-cookbooks/synthetic-monitoring) with `--report` before the end of May 2026.

> This Synthetic Monitoring build was triggered by a deprecated method. Migrate to Blackfire Player with --report before the end of May 2026.

The following classes are deprecated:
* `Blackfire\Build\Build`
* `Blackfire\Build\Scenario`
* `Blackfire\Build\BuildHelper`
* `Blackfire\Bridge\PhpUnit\BlackfireBuildExtension`
* `Blackfire\Bridge\PhpUnit\BlackfireBuildExtension9`
* `Blackfire\Bridge\PhpUnit\BlackfireBuildExtension10`
* `Blackfire\Bridge\PhpUnit\BlackfireBuildSubscriber`
* `Blackfire\Bridge\PhpUnit\BlackfireTestCaseTrait`
* `Blackfire\Bridge\PhpUnit\Laravel\BlackfireBuildExtension`
* `Blackfire\Bridge\Laravel\BlackfireTestCase`
* `Blackfire\Bridge\Laravel\BlackfireTestArtisanCommandsTrait`
* `Blackfire\Bridge\Behat\BlackfireExtension\Event\BuildSubscriber`
* `Blackfire\Bridge\Behat\BlackfireExtension\Event\ScenarioSubscriber`

The following methods are deprecated:
* `Blackfire\Client::startBuild()`
* `Blackfire\Client::closeBuild()`
* `Blackfire\Client::startScenario()`
* `Blackfire\Client::closeScenario()`
* `Blackfire\Client::getBuildReport()`
* `Blackfire\Client::addStep()`
* `Blackfire\Profile\Configuration::setScenario()`
* `Blackfire\Profile\Configuration::setBuildUuid()`
* `Blackfire\LoopClient::generateBuilds()`

To v2.3.0
---------

* Methods `Blackfire\Build::incScenario`, `Blackfire\Scenario::incJob` are
  deprecated without replacement.
* Method `Blackfire\Client::getScenarioReport` is deprecated. Use
  `Blackfire\Client::getBuildReport` instead.
* Method `Blackfire\Client::addJobInScenario` is deprecated. Set the scenario
  with `Blackfire\Profile\Configuration::setScenario` then call `Blackfire\Client::endProbe`

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
