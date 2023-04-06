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

class OctaneProfilerMiddleware
{
    private $profiler;

    public function __construct()
    {
        $this->profiler = new OctaneProfiler();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, \Closure $next)
    {
        if (!method_exists(\BlackfireProbe::class, 'setAttribute')) {
            return;
        }

        try {
            $this->profiler->start($request);
            $response = $next($request);
            \BlackfireProbe::setAttribute('http.status_code', $response->status());
        } finally {
            $this->profiler->stop($request, $response ?? null);
        }

        return $response;
    }
}
