<?php

namespace Blackfire\Bridge\Laravel;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpClient\HttpClient;

class InstrumentedTestRequests
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!app()->runningUnitTests()) {
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

        return $httpClient->request(
            $request->getMethod(),
            $request->getUri(),
            array(
                'headers' => $headers,
                'body' => $request->request->all(),
            ),
        );
    }
}
