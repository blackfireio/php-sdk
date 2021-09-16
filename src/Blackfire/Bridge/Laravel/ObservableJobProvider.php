<?php

namespace Blackfire\Bridge\Laravel;

use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;


class ObservableJobProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::before(function (JobProcessing $event) {
            $transactionName = $event->job->payload()['displayName'] ?? 'Job';
            \BlackfireProbe::startTransaction();
            \BlackfireProbe::setTransactionName($transactionName);
        });

        Queue::after(function (JobProcessed $event) {
            \BlackfireProbe::stopTransaction();
        });
    }
}
