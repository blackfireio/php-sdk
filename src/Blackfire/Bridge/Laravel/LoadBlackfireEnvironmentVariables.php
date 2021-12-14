<?php

namespace Blackfire\Bridge\Laravel;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
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
        $hasBlackfireTestHeaders = $request->headers->has('X-BLACKFIRE-LARAVEL-TESTS') && $request->headers->has('X-BLACKFIRE-QUERY');
        if (!$hasBlackfireTestHeaders) {
            return;
        }

        try {
            $dotenv = Dotenv::createImmutable(base_path(), '.env.testing');
            $dotenv->safeload();
        } catch (InvalidFileException $e) {
        }
    }
}
