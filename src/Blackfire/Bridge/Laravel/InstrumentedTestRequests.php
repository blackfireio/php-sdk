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

use Illuminate\Http\Request;
use Symfony\Component\HttpClient\HttpClient;

class InstrumentedTestRequests
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        if ('testing' !== env('APP_ENV')) {
            return $next($request);
        }

        if (!$request->headers->has('X-BLACKFIRE-QUERY')) {
            return $next($request);
        }

        if ($request->headers->has('X-BLACKFIRE-LARAVEL-TESTS')) {
            return $next($request);
        }

        $headers = $request->headers->all() ?? array();
        $headers['X-BLACKFIRE-LARAVEL-TESTS'] = array(true);

        $httpClient = HttpClient::create();

        $httpClient->request(
            $request->getMethod(),
            $request->getUri(),
            array(
                'headers' => $headers,
                'body' => $request->request->all(),
            ),
        );

        return $next($request);
    }
}
