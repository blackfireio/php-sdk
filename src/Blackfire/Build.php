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

class Build
{
    private $env;
    private $data;
    private $jobCount;

    public function __construct($env, $data)
    {
        $this->env = $env;
        $this->data = $data;
        $this->jobCount = 0;
    }

    public function getEnv()
    {
        return $this->env;
    }

    public function getUuid()
    {
        return $this->data['uuid'];
    }

    public function getUrl()
    {
        return isset($this->data['_links']['report']['href']) ? $this->data['_links']['report']['href'] : null;
    }

    public function incJob()
    {
        ++$this->jobCount;
    }

    public function getJobCount()
    {
        return $this->jobCount;
    }
}
