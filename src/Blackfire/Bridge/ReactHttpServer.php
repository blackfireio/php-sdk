<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge;

use React\Http\Request;
use React\Http\Response;

class ReactHttpServer
{
    public function handle(Request $request, Response $response)
    {
        $headers = array_change_key_case($request->getHeaders(), CASE_LOWER);

        // Only enable when the X-Blackfire-Query header is present
        if (!isset($headers['x-blackfire-query'])) {
            return array();
        }

        $probe = new \BlackfireProbe($headers['x-blackfire-query']);

        // Stop if it failed
        if (!$probe->enable()) {
            return array();
        }

        // Stop profiling once the request ends
        $response->on('end', array($probe, 'close'));

        // Return the header
        $header = explode(':', $probe->getResponseLine(), 2);

        return array('x-'.$header[0] => $header[1]);
    }
}
