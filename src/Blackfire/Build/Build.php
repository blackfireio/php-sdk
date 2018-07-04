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

class Build
{
    private $env;
    private $data;
    private $scenarioCount;

    public function __construct($env, $data)
    {
        $this->env = $env;
        $this->data = $data;
        $this->scenarioCount = 0;
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
        ++$this->scenarioCount;
    }

    public function getScenarioCount()
    {
        return $this->scenarioCount;
    }

    public function getUrl()
    {
        return isset($this->data['_links']['report']['href']) ? $this->data['_links']['report']['href'] : null;
    }
}
