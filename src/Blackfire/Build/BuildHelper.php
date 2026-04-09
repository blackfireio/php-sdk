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

/**
 * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
 */
class BuildHelper
{
    /** @var BuildHelper */
    private static $instance;

    /** @var Client */
    private $blackfire;

    /** @var Build */
    private $currentBuild;

    /** @var bool */
    private $buildDeferred = false;

    /** @var array */
    private $buildOptions = array();

    /** @var array<string, Scenario> */
    private $scenarios = array();

    /** @var bool */
    private $enabled = true;

    /** @var ?string */
    private $blackfireEnvironmentId;

    public function __construct()
    {
        $this->blackfire = new Client();
    }

    /**
     * @return BuildHelper
     *
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public static function getInstance()
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

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
     * @return Build
     *
     * @throws \RuntimeException
     *
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function startBuild($blackfireEnvironment, $buildTitle, $externalId = null, $externalParentId = null, $triggerName = 'Build SDK')
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

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
     *
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function deferBuild($blackfireEnvironment, $buildTitle, $externalId = null, $externalParentId = null, $triggerName = 'Build SDK')
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

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
     * @return Build
     *
     * @throws \RuntimeException
     *
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function startDeferredBuild()
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

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
     *
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function endCurrentBuild()
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

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
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function createScenario(?string $scenarioTitle = null, ?string $scenarioKey = 'current'): Scenario
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

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

        return $this->scenarios[$scenarioKey] = $this->blackfire->startScenario($this->currentBuild, $options);
    }

    /**
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function endCurrentScenario()
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        if (!$this->hasCurrentScenario()) {
            throw new \RuntimeException('A Blackfire scenario must be started to be able to end it.');
        }

        $this->endScenario('current');
    }

    /**
     * @return bool
     */
    public function hasCurrentScenario()
    {
        return $this->hasScenario('current');
    }

    /**
     * @return Scenario|null
     */
    public function getCurrentScenario()
    {
        return $this->scenarios['current'] ?? null;
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

    public function hasScenario(string $scenarioKey = 'current'): bool
    {
        return array_key_exists($scenarioKey, $this->scenarios);
    }

    public function getScenario(string $scenarioKey): Scenario
    {
        if (!$this->hasScenario($scenarioKey)) {
            throw new \RuntimeException('No Scenario registered with that key');
        }

        return $this->scenarios[$scenarioKey];
    }

    /**
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function endScenario(string $scenarioKey): void
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $scenario = $this->getScenario($scenarioKey);
        $this->getBlackfireClient()->closeScenario($scenario);

        unset($this->scenarios[$scenarioKey]);
    }

    /**
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function createRequest(string $scenarioKey, ?string $title = null): Request
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        return $this->getBlackfireClient()->createRequest(
            $this->getConfigurationForScenario($scenarioKey, $title)
        );
    }

    /**
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function createProbe(string $scenarioKey, ?string $title = null): Probe
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        return $this->getBlackfireClient()->createProbe(
            $this->getConfigurationForScenario($scenarioKey, $title)
        );
    }

    /**
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function endProbe(Probe $probe): void
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->getBlackfireClient()->endProbe($probe);
    }

    /**
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function getConfigurationForScenario(string $scenarioKey, $title = null): Configuration
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        return (new Configuration())
            ->setScenario($this->getScenario($scenarioKey))
            ->setMetadata('skip_timeline', 'true')
            ->setTitle($title)
        ;
    }

    public function hasAnyScenario(): bool
    {
        return !empty($this->scenarios);
    }

    /**
     * @deprecated since blackfire/php-sdk 2.6, will be removed in 3.0.
     */
    public function endAllScenarios(): void
    {
        @trigger_error(sprintf('The method "%s" is deprecated since blackfire/php-sdk 2.6 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        foreach (array_keys($this->scenarios) as $scenarioKey) {
            $this->endScenario($scenarioKey);
        }
    }

    public function getBlackfireEnvironmentId(): ?string
    {
        return $this->blackfireEnvironmentId;
    }

    public function setBlackfireEnvironmentId(string $blackfireEnvironmentId): self
    {
        $this->blackfireEnvironmentId = $blackfireEnvironmentId;

        return $this;
    }
}
