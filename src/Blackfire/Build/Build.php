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

use http\Exception\InvalidArgumentException;

class Build
{
    private $env;
    private $data;
    private $scenarioCount;
    private $scenarios;
    private $status;
    private $version;

    public function __construct($env, $data)
    {
        $this->env = $env;
        $this->data = $data;
        $this->scenarioCount = 0;
        $this->scenarios = array();
        $this->status = 'in_progress';
        $this->version = 1;
    }

    public function getEnv()
    {
        return $this->env;
    }

    public function getUuid()
    {
        return $this->data['uuid'];
    }

    public function incScenario()
    {
        @trigger_error('The method "%s" is deprecated since blackfire/php-sdk 2.3 and will be removed in 3.0.', E_USER_DEPRECATED);

        ++$this->scenarioCount;
    }

    public function getScenarioCount()
    {
        return $this->scenarioCount + count($this->scenarios);
    }

    public function getUrl()
    {
        return isset($this->data['_links']['report']['href']) ? $this->data['_links']['report']['href'] : null;
    }

    public function addScenario(Scenario $scenario)
    {
        $this->scenarios[] = $scenario;
    }

    /**
     * @return Scenario[]
     */
    public function getScenarios()
    {
        return $this->scenarios;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        if (!in_array($status, array('todo', 'in_progress', 'done'), true)) {
            throw new InvalidArgumentException();
        }

        $this->status = $status;
    }

    /**
     * @internal
     */
    public function getNextVersion()
    {
        ++$this->version;

        return $this->version;
    }
}
