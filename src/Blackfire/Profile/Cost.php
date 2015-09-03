<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Profile;

class Cost
{
    private $envelope;

    public function __construct($envelope)
    {
        $this->envelope = $envelope;
    }

    public function getWallTime()
    {
        return $this->envelope['wt'];
    }

    public function getCpu()
    {
        return $this->envelope['cpu'];
    }

    public function getIo()
    {
        return $this->envelope['io'];
    }

    public function getNetwork()
    {
        return $this->envelope['nw'];
    }

    public function getPeakMemoryUsage()
    {
        return $this->envelope['pmu'];
    }

    public function getMemoryUsage()
    {
        return $this->envelope['mu'];
    }
}
