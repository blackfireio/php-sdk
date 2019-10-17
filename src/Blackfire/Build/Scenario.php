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

class Scenario
{
    private $build;
    private $data;
    private $jobCount;

    public function __construct(Build $build, $data)
    {
        $this->build = $build;
        $this->data = $data;
        $this->jobCount = 0;
    }

    public function getEnv()
    {
        return $this->build->getEnv();
    }

    public function getBuild()
    {
        return $this->build;
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
