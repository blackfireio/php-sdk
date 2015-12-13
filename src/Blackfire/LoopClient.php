<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire;

use Blackfire\Profile;
use Blackfire\Profile\Configuration;
use Blackfire\Exception\LogicException;
use Blackfire\Exception\RuntimeException;

class LoopClient
{
    private $client;
    private $maxIterations;
    private $currentIteration = 0;
    private $probe;
    private $signal = false;
    private $enabled = true;
    private $reference = false;
    private $referenceId;

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

        $self = $this;
        pcntl_signal($signal, function ($signo) use ($self) {
            $self->enabled = true;
        });

        $this->signal = true;
        $this->enabled = false;
    }

    /**
     * @param int $referenceId The reference ID to use (rolling reference)
     * @param int $signal      A signal that triggers profiling for a reference (like SIGUSR2)
     */
    public function setReference($referenceId, $signal = null)
    {
        $this->referenceId = $referenceId;

        if (null !== $signal) {
            if (!extension_loaded('pcntl')) {
                throw new RuntimeException('pcntl must be available to use signals.');
            }

            if (null === $this->signal) {
                throw new LogicException('Cannot set a reference signal without a signal.');
            }

            $self = $this;
            pcntl_signal($signal, function ($signo) use ($self, $referenceId) {
                $self->reference = true;
                $self->enabled = true;
            });
        }
    }

    public function startLoop(Configuration $config = null)
    {
        if ($this->signal) {
            pcntl_signal_dispatch();
        }

        if (!$this->enabled) {
            return;
        }

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

        $this->probe->close();

        ++$this->currentIteration;
        if ($this->currentIteration === $this->maxIterations) {
            return $this->endProbe();
        }
    }

    private function createProbe($config)
    {
        if (null === $config) {
            $config = new Configuration();
        } else {
            $config = clone $config;
        }

        $config->setSamples($this->maxIterations);

        if (null !== $this->referenceId) {
            $config->setReference($this->referenceId);
        }

        if ($this->reference) {
            $config->setAsReference();
        }

        return $this->client->createProbe($config, false);
    }

    private function endProbe()
    {
        $this->currentIteration = 0;

        if ($this->signal) {
            $this->enabled = false;
            $this->reference = false;
        }

        return $this->client->endProbe($this->probe);
    }
}
