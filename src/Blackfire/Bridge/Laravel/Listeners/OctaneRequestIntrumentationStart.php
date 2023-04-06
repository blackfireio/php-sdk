<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Laravel\Listeners;

class OctaneRequestIntrumentationStart
{
    /**
     * Handle the event.
     *
     * @param mixed $event
     */
    public function handle($event): void
    {
        if (!method_exists(\BlackfireProbe::class, 'startTransaction') || !method_exists(\BlackfireProbe::class, 'setAttribute')) {
            return;
        }

        \BlackfireProbe::startTransaction();

        $request = $event->request;
        if (!$request) {
            return;
        }
        \BlackfireProbe::setAttribute('http.target', $request->path());
        \BlackfireProbe::setAttribute('http.url', $request->url());
        \BlackfireProbe::setAttribute('http.method', $request->method());
        \BlackfireProbe::setAttribute('http.host', $request->getHost());
        \BlackfireProbe::setAttribute('host', $request->getHost());
        \BlackfireProbe::setAttribute('framework', 'Laravel');
    }
}
