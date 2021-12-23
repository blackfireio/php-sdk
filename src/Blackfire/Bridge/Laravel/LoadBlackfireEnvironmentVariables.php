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

use Dotenv\Dotenv;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class LoadBlackfireEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $request = Request::capture();
        if (!($request->headers->has('X-BLACKFIRE-LARAVEL-TESTS') && $request->headers->has('X-BLACKFIRE-QUERY'))) {
            return;
        }

        $dotenv = Dotenv::createImmutable(base_path(), '.env.testing');
        $dotenv->load();

        if ('testing' !== env('APP_ENV')) {
            throw new \RuntimeException('The .env.testing file should contain APP_ENV=testing');
        }
    }
}
