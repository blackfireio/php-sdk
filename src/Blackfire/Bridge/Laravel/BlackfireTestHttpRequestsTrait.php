<?php

namespace Blackfire\Bridge\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;

trait BlackfireTestHttpRequestsTrait
{
    /**
     * Create a new HTTP kernel instance.
     *
     * @return void
     */
    public function __construct(Application $app, Router $router)
    {
        array_unshift($this->bootstrappers, LoadBlackfireEnvironmentVariables::class);

        parent::__construct($app, $router);
    }
}
