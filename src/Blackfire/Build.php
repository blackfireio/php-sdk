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

@trigger_error('The \Blackfire\Build class is deprecated since blackfire/php-sdk 1.14 and will be removed in 2.0. Use the class \Blackfire\Build\Scenario instead.', E_USER_DEPRECATED);

/**
 * @deprecated since 1.14, to be removed in 2.0. Use the class \Blackfire\Build\Scenario instead.
 */
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
