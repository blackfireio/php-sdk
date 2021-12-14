<?php

namespace Blackfire\Build;

use Blackfire\Probe;
use Blackfire\Profile\Configuration;
use Blackfire\Profile\Request;

class ParallelScenariosBuildHelper extends BuildHelper
{
    /** @var array<string, Scenario> */
    public static $scenarios = array();

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
        if (!$scenarioTitle) {
            $scenarioTitle = $scenarioKey;
        }

        if ($this->hasScenario($scenarioKey)) {
            throw new \RuntimeException('A Scenario already registered with that key');
        }

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
}
