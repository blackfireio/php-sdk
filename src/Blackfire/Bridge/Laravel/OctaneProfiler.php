<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Laravel;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OctaneProfiler
{
    /** @var \BlackfireProbe */
    protected $probe;

    /** @var Request */
    protected $request;

    public function start(Request $request): bool
    {
        if (!method_exists(\BlackfireProbe::class, 'setAttribute')) {
            return false;
        }

        if (!$request->headers->has('x-blackfire-query')) {
            return false;
        }

        if ($this->probe) {
            // profiling may have been activated on a concurrent thread
            return false;
        }

        $this->probe = new \BlackfireProbe($request->headers->get('x-blackfire-query'));

        $this->request = $request;
        if (!$this->probe->enable()) {
            \BlackfireProbe::setAttribute('profileTitle', $request->url());
            $this->reset();
            throw new \UnexpectedValueException('Cannot enable Blackfire profiler');
        }

        return true;
    }

    /**
     * @param \Swoole\Http\Request       $request
     * @param ?Response|RedirectResponse $response
     */
    public function stop(Request $request, $response = null): bool
    {
        if (!class_exists(\BlackfireProbe::class)) {
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
            list($probeHeaderName, $probeHeaderValue) = explode(':', $this->probe->getResponseLine(), 2);
            $response->header(strtolower("x-$probeHeaderName"), trim($probeHeaderValue));
        }
        $this->reset();

        return true;
    }

    public function reset(): void
    {
        if ($this->probe && $this->probe->isEnabled()) {
            $this->probe->close();
        }
        $this->probe = null;
        $this->request = null;
    }
}
