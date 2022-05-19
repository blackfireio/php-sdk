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
        if (!class_exists(\BlackfireProbe::class)) {
            return;
        }

        Event::listen(
            array(
                CommandStarting::class,
            ),
            function ($event) {
                $transactionName = 'artisan '.($event->input->__toString() ?? 'Unnamed Command');
                if (version_compare(phpversion('blackfire'), '1.78.0', '>=')) {
                    \BlackfireProbe::startTransaction($transactionName);
                } else {
                    \BlackfireProbe::startTransaction();
                    \BlackfireProbe::setTransactionName($transactionName);
                }
            }
        );

        Event::listen(
            array(
                ScheduledTaskStarting::class,
            ),
            function ($event) {
                $task = $event->task;
                $transactionName = $task->expression.' '.$task->command;
                if (version_compare(phpversion('blackfire'), '1.78.0', '>=')) {
                    \BlackfireProbe::startTransaction($transactionName);
                } else {
                    \BlackfireProbe::startTransaction();
                    \BlackfireProbe::setTransactionName($transactionName);
                }
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
