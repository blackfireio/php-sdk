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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ReactHttpServer
 *
 * @package Blackfire\Bridge
 */
class ReactHttpServer
{
    /**
     * @var \BlackfireProbe
     */
    private $probe;

    /**
     * Start profiling with Blackfire if header is present
     *
     * @param ServerRequestInterface $request
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $headers = array_change_key_case($request->getHeaders(), CASE_LOWER);

        // Only enable when the X-Blackfire-Query header is present
        if (!isset($headers['x-blackfire-query'])) {
            return;
        }

        $this->probe = new \BlackfireProbe($headers['x-blackfire-query'][0]);
        // Stop if it failed
        if (!$this->probe->enable()) {
            fwrite(STDERR, 'Could not start blackfire probe');

            return;
        }
    }

    /**
     * Stop profiling and add Blackfire headers if needed
     *
     * @param array $headers
     * @return array
     */
    public function handleHeaders($headers)
    {
        if ($this->probe) {
            $this->probe->close();

            // Return the header
            $header = explode(':', $this->probe->getResponseLine(), 2);

            $headers['x-' . $header[0]] = $header[1];

            $this->probe = null;
        }

        return $headers;
    }

}
