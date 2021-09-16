<?php

namespace Blackfire\Bridge\Laravel;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;


class ObservableCommandProvider extends ServiceProvider
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
        Event::listen(CommandStarting::class, function(CommandStarting $event) {
            $transactionName = 'artisan ' . ($event->input->__toString() ?? 'Unnamed Command');
            \BlackfireProbe::startTransaction();
            \BlackfireProbe::setTransactionName($transactionName);
        });

        Event::listen(CommandFinished::class, function() {
            \BlackfireProbe::stopTransaction();
        });
    }
}
