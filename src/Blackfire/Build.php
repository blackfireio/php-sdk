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
    private $app;
    private $uuid;
    private $jobCount;

    public function __construct($app, $uuid)
    {
        $this->app = $app;
        $this->uuid = $uuid;
        $this->jobCount = 0;
    }

    public function getApp()
    {
        return $this->app;
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
