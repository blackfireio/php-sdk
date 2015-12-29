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

use Blackfire\Profile\Request;

/**
 * Represents a Blackfire Probe.
 *
 * Instances of this class should never be created directly.
 * Use Blackfire\Client instead.
 */
class Probe
{
    private $probe;
    private $request;

    /**
     * @internal
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->probe = new \BlackfireProbe($request->getToken());

        if ($yaml = $request->getYaml()) {
            $this->probe->setConfiguration($yaml);
        }
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function discard()
    {
        return $this->probe->discard();
    }

    public function enable()
    {
        return $this->probe->enable();
    }

    public function disable()
    {
        return $this->probe->disable();
    }

    public function close()
    {
        return $this->probe->close();
    }
}
