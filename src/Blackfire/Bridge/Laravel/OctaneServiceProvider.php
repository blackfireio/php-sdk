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

use Blackfire\Bridge\Laravel\Listeners\OctaneRequestIntrumentationStart;
use Blackfire\Bridge\Laravel\Listeners\OctaneRequestIntrumentationStop;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;

class OctaneServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (!class_exists(\BlackfireProbe::class)) {
            return;
        }

        Event::listen(
            array(
                RequestReceived::class,
            ),
            OctaneRequestIntrumentationStart::class
        );

        Event::listen(
            array(
                RequestHandled::class,
                RequestTerminated::class,
            ),
            OctaneRequestIntrumentationStop::class
        );
    }
}
