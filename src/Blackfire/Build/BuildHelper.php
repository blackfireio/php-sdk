<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Build;

use Blackfire\Client;
use Blackfire\Probe;
use Blackfire\Profile\Configuration;
use Blackfire\Profile\Request;
use Blackfire\Report;

class BuildHelper
{
    /** @var BuildHelper */
    protected static $instance;

    /** @var Client */
    protected $blackfire;

    /** @var Build */
    protected $currentBuild;

    /** @var bool */
    protected $buildDeferred = false;

    /** @var array */
    protected $buildOptions = array();

    /** @var array<string, Scenario> */
    protected static $scenarios = array();

    /** @var bool */
    protected $enabled = true;

    public function __construct()
    {
        $this->blackfire = new Client();
    }

    /**
     * @return BuildHelper
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return Client
     */
    public function getBlackfireClient()
    {
        return $this->blackfire;
    }

    /**
     * @param string      $blackfireEnvironment The Blackfire environment name or UUID
     * @param string      $buildTitle           The build title
     * @param string|null $externalId           Reference for this build
     * @param string|null $externalParentId     Reference to compare this build to
     * @param string      $triggerName          Name of the build trigger
     *
     * @throws \RuntimeException
     *
     * @return Build
     */
    public function startBuild($blackfireEnvironment, $buildTitle, $externalId = null, $externalParentId = null, $triggerName = 'Build SDK')
    {
        if ($this->hasCurrentBuild()) {
            throw new \RuntimeException('A Blackfire build was already started.');
        }

        if (!$this->enabled) {
            throw new \RuntimeException('Cannot start a build because Blackfire builds are globally disabled.');
        }

        $options = array(
            'trigger_name' => $triggerName,
            'title' => $buildTitle,
        );
        if (null !== $externalId) {
            $options['external_id'] = $externalId;
        }
        if (null !== $externalParentId) {
            $options['external_parent_id'] = $externalParentId;
        }

        $this->currentBuild = $this->blackfire->startBuild($blackfireEnvironment, $options);

        return $this->currentBuild;
    }

    /**
     * Defers the build start at the first scenario.
     *
     * @param string      $blackfireEnvironment The Blackfire environment name or UUID
     * @param string      $buildTitle           The build title
     * @param string|null $externalId           Reference for this build
     * @param string|null $externalParentId     Reference to compare this build to
     * @param string      $triggerName          Name of the build trigger
     *
     * @throws \RuntimeException
     */
    public function deferBuild($blackfireEnvironment, $buildTitle, $externalId = null, $externalParentId = null, $triggerName = 'Build SDK')
    {
        if ($this->hasCurrentBuild()) {
            throw new \RuntimeException('A Blackfire build was already started.');
        }

        if (!$this->enabled) {
            throw new \RuntimeException('Cannot start a build because Blackfire builds are globally disabled.');
        }

        $this->buildDeferred = true;
        $this->buildOptions = array(
            'environment' => $blackfireEnvironment,
            'trigger_name' => $triggerName,
            'title' => $buildTitle,
            'external_id' => $externalId,
            'external_parent_id' => $externalParentId,
        );
    }

    /**
     * Starts a build that has been deferred.
     *
     * @throws \RuntimeException
     *
     * @return Build
     */
    public function startDeferredBuild()
    {
        if (!$this->buildDeferred) {
            throw new \RuntimeException('There is no deferred build to start.');
        }

        return $this->startBuild(
            $this->buildOptions['environment'],
            $this->buildOptions['title'],
            $this->buildOptions['external_id'],
            $this->buildOptions['external_parent_id'],
            $this->buildOptions['trigger_name']
        );
    }

    public function isBuildDeferred()
    {
        return $this->buildDeferred;
    }

    /**
     * @return Report
     */
    public function endCurrentBuild()
    {
        if (!$this->hasCurrentBuild()) {
            throw new \RuntimeException('A Blackfire build must be started to be able to end it.');
        }

        $report = $this->blackfire->closeBuild($this->currentBuild);
        unset($this->currentBuild);

        return $report;
    }

    /**
     * @return bool
     */
    public function hasCurrentBuild()
    {
        return isset($this->currentBuild);
    }

    /**
     * @return Build|null
     */
    public function getCurrentBuild()
    {
        return $this->currentBuild;
    }

    /**
     * @param string $scenarioTitle The scenario title
     *
     * @return Scenario
     */
    public function createScenario($scenarioTitle = null)
    {
        if (!$this->enabled) {
            throw new \RuntimeException('Unable to create a Scenario because Blackfire build is globally disabled.');
        }

        if ($this->isBuildDeferred() && !$this->hasCurrentBuild()) {
            $this->startDeferredBuild();
        }

        if (!$this->hasCurrentBuild()) {
            throw new \RuntimeException('A Blackfire build must be started to be able create a scenario.');
        }

        if ($this->hasCurrentScenario()) {
            throw new \RuntimeException('A Blackfire scenario is already running.');
        }

        $options = array();
        if (null !== $scenarioTitle) {
            $options['title'] = $scenarioTitle;
        }

        return $this->scenarios['currentScenario'] = $this->blackfire->startScenario($this->currentBuild, $options);
    }

    public function endCurrentScenario()
    {
        if (!$this->hasCurrentScenario()) {
            throw new \RuntimeException('A Blackfire scenario must be started to be able to end it.');
        }

        $this->closeScenario('currentScenario');
    }

    /**
     * @return bool
     */
    public function hasCurrentScenario()
    {
        return array_key_exists('currentScenario', $this->scenarios);
    }

    /**
     * @return Scenario|null
     */
    public function getCurrentScenario()
    {
        return $this->scenarios['currentScenario'] ?? null;
    }

    /**
     * @param bool $enabled
     *
     * @return BuildHelper
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    public function hasScenario(string $scenarioKey): bool
    {
        return array_key_exists($scenarioKey, self::$scenarios);
    }

    public function getScenario(string $scenarioKey): Scenario
    {
        if (!$this->hasScenario($scenarioKey)) {
            throw new \RuntimeException('No Scenario registered with that key');
        }

        return self::$scenarios[$scenarioKey];
    }

    public function startScenario(string $scenarioKey, string $scenarioTitle = null): void
    {
        if (!$this->enabled) {
            throw new \RuntimeException('Unable to create a Scenario because Blackfire build is globally disabled.');
        }

        if (!$scenarioTitle) {
            $scenarioTitle = $scenarioKey;
        }

        if ($this->hasScenario($scenarioKey)) {
            throw new \RuntimeException('A Scenario already registered with that key');
        }

        $this->startBuildIfNeeded();
        $options = array('title' => $scenarioTitle);
        $scenario = $this->getBlackfireClient()->startScenario($this->getCurrentBuild(), $options);

        self::$scenarios[$scenarioKey] = $scenario;
    }

    public function closeScenario(string $scenarioKey): void
    {
        $scenario = $this->getScenario($scenarioKey);
        $this->getBlackfireClient()->closeScenario($scenario);

        unset(self::$scenarios[$scenarioKey]);
    }

    public function createRequest(string $scenarioKey, string $title = null): Request
    {
        $configuration = $this->getConfiguration($scenarioKey, $title);

        return $this->getBlackfireClient()->createRequest($configuration);
    }

    public function createProbe(string $scenarioKey, string $title = null): Probe
    {
        $configuration = $this->getConfiguration($scenarioKey, $title);

        return $this->getBlackfireClient()->createProbe($configuration);
    }

    public function endProbe(Probe $probe): void
    {
        $this->getBlackfireClient()->endProbe($probe);
    }

    public function getConfiguration(string $scenarioKey, $title = null): Configuration
    {
        $scenario = $this->getScenario($scenarioKey);

        return (new Configuration())
            ->setScenario($scenario)
            ->setMetadata('skip_timeline', 'true')
            ->setTitle($title)
        ;
    }

    public function hasAnyScenario(): bool
    {
        return !empty(self::$scenarios);
    }

    public function endAllScenarios(): void
    {
        $scenarioKeys = array_keys(self::$scenarios);
        foreach ($scenarioKeys as $scenarioKey) {
            $this->closeScenario($scenarioKey);
        }
    }

    private function startBuildIfNeeded(): void
    {
        if ($this->hasCurrentBuild()) {
            return;
        }

        if ($this->isBuildDeferred()) {
            $this->startDeferredBuild();

            return;
        }

        throw new \RuntimeException('Unable to start build.');
    }
}
