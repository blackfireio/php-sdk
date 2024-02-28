<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire;

use Blackfire\Exception\LogicException;
use Blackfire\Exception\RuntimeException;
use Blackfire\Profile\Configuration as ProfileConfiguration;

class LoopClient
{
    private $client;
    private $maxIterations;
    private $currentIteration = 0;
    private $probe;
    private $signal = false;
    private $enabled = true;
    private $running = false;
    private $build;
    private $scenario;
    private $buildFactory;
    private $env = false;

    /**
     * @param int $maxIterations The number of iterations
     */
    public function __construct(Client $client, $maxIterations)
    {
        $this->client = $client;
        $this->maxIterations = $maxIterations;
    }

    /**
     * @param int $signal A signal that triggers profiling (like SIGUSR1)
     */
    public function setSignal($signal)
    {
        if (!extension_loaded('pcntl')) {
            throw new RuntimeException('pcntl must be available to use signals.');
        }

        $enabled = &$this->enabled;
        pcntl_signal($signal, function ($signo) use (&$enabled) {
            $enabled = true;
        });

        $this->signal = true;
        $this->enabled = false;
    }

    /**
     * @param string|null   $env          The environment name (or null to use the one configured on the client)
     * @param callable|null $buildFactory An optional factory callable that creates build instances
     */
    public function generateBuilds($env = null, $buildFactory = null)
    {
        $this->env = $env;
        $this->buildFactory = $buildFactory;
    }

    public function startLoop(?ProfileConfiguration $config = null)
    {
        if ($this->signal) {
            pcntl_signal_dispatch();
        }

        if (!$this->enabled) {
            return;
        }

        if ($this->running) {
            throw new LogicException('Unable to start a loop as one is already running.');
        }

        $this->running = true;

        if (0 === $this->currentIteration) {
            $this->probe = $this->createProbe($config);
        }

        $this->probe->enable();
    }

    /**
     * @return Profile|null
     */
    public function endLoop()
    {
        if (!$this->enabled) {
            return;
        }

        if (null === $this->probe) {
            return;
        }

        if (!$this->running) {
            throw new LogicException('Unable to stop a loop as none is running.');
        }

        $this->running = false;

        $this->probe->close();

        ++$this->currentIteration;
        if ($this->currentIteration === $this->maxIterations) {
            return $this->endProbe();
        }
    }

    private function createProbe($config)
    {
        if (null === $config) {
            $config = new ProfileConfiguration();
        } else {
            $config = clone $config;
        }

        $config->setSamples($this->maxIterations);

        if (false !== $this->env) {
            $this->scenario = $this->client->startScenario();
            $config->setScenario($this->scenario);
        }

        return $this->client->createProbe($config, false);
    }

    private function endProbe()
    {
        $this->currentIteration = 0;

        if ($this->signal) {
            $this->enabled = false;
        }

        $profile = $this->client->endProbe($this->probe);

        if (null !== $this->scenario) {
            $this->client->closeScenario($this->scenario);
            $this->client->closeBuild($this->build);

            $this->scenario = null;
            $this->build = null;
        }

        if (null !== $this->build) {
            $this->client->endBuild($this->build);

            $this->build = null;
        }

        return $profile;
    }
}
