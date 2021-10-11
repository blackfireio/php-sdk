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

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ObservableCommandProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(
            array(
                CommandStarting::class,
                ScheduledTaskStarting::class,
            ),
            function ($event) {
                $transactionName = 'artisan '.($event->input->__toString() ?? 'Unnamed Command');
                \BlackfireProbe::startTransaction();
                \BlackfireProbe::setTransactionName($transactionName);
            }
        );

        Event::listen(
            array(
                CommandFinished::class,
                ScheduledTaskFinished::class,
                ScheduledTaskFailed::class,
            ),
            function () {
                \BlackfireProbe::stopTransaction();
            }
        );
    }
}
