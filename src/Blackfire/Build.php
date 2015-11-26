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
    private $uuid;
    private $jobCount;

    public function __construct($env, $uuid)
    {
        $this->env = $env;
        $this->uuid = $uuid;
        $this->jobCount = 0;
    }

    public function getEnv()
    {
        return $this->env;
    }

    public function getUuid()
    {
        return $this->uuid;
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
