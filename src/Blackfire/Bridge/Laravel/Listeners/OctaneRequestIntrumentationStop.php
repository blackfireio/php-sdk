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

use Illuminate\Routing\Route;

class OctaneRequestIntrumentationStop
{
    /**
     * Handle the event.
     *
     * @param mixed $event
     */
    public function handle($event): void
    {
        if (!class_exists(\BlackfireProbe::class)) {
            return;
        }

        $request = $event->request;
        if (!$request) {
            \BlackfireProbe::stopTransaction();

            return;
        }

        $transactionName = $request->path();
        $route = $request->route();
        if ($route instanceof Route && is_string($route->getAction('uses'))) {
            $transactionName = $route->getActionName();
        }
        \BlackfireProbe::setTransactionName($transactionName);

        \BlackfireProbe::stopTransaction();
    }
}
