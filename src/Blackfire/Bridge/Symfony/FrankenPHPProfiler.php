<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Symfony;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FrankenPHPProfiler
{
    /**
     * @var \BlackfireProbe|null
     */
    private $probe;

    /**
     * @var Request|null
     */
    private $request;

    /**
     * @return bool
     */
    public function start(Request $request)
    {
        if (!method_exists(\BlackfireProbe::class, 'setAttribute')) {
            return false;
        }

        if (!$request->headers->has('x-blackfire-query')) {
            return false;
        }

        if ($this->probe) {
            return false;
        }

        $this->probe = new \BlackfireProbe($request->headers->get('x-blackfire-query'));
        $this->request = $request;

        if (!$this->probe->enable()) {
            \BlackfireProbe::setAttribute('profileTitle', $request->getUri());
            $this->reset();
            throw new \UnexpectedValueException('Cannot enable Blackfire profiler');
        }

        return true;
    }

    /**
     * @return bool
     */
    public function stop(Request $request, ?Response $response = null)
    {
        if (!class_exists(\BlackfireProbe::class, false)) {
            return false;
        }

        if (!$this->probe) {
            return false;
        }

        if (!$this->probe->isEnabled()) {
            return false;
        }

        if ($this->request !== $request) {
            return false;
        }

        $this->probe->close();

        if ($response) {
            $responseLine = $this->probe->getResponseLine();
            if ($responseLine) {
                list($probeHeaderName, $probeHeaderValue) = explode(':', $responseLine, 2);
                $response->headers->set('x-'.strtolower($probeHeaderName), trim($probeHeaderValue));
            }
        }

        $this->reset();

        return true;
    }

    /**
     * @return void
     */
    public function reset()
    {
        if ($this->probe && $this->probe->isEnabled()) {
            $this->probe->close();
        }

        $this->probe = null;
        $this->request = null;
    }
}
