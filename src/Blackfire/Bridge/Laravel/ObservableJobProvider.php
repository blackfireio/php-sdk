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

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class ObservableJobProvider extends ServiceProvider
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

        Queue::before(function (JobProcessing $event) {
            $transactionName = $event->job->payload()['displayName'] ?? 'Job';
            if (version_compare(phpversion('blackfire'), '1.78.0', '>=')) {
                \BlackfireProbe::startTransaction($transactionName);
            } else {
                \BlackfireProbe::startTransaction();
                \BlackfireProbe::setTransactionName($transactionName);
            }
        });

        Queue::after(function (JobProcessed $event) {
            \BlackfireProbe::stopTransaction();
        });

        Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
            \BlackfireProbe::stopTransaction();
        });
    }
}
