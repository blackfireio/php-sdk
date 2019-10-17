<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Profile;

/**
 * Represents a Blackfire Profile Cost.
 *
 * Instances of this class should never be created directly.
 */
class Cost
{
    private $envelope;

    /**
     * @internal
     */
    public function __construct($envelope)
    {
        $this->envelope = $envelope;
    }

    public function getCount()
    {
        return $this->envelope['ct'];
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
