<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Helper used to profile React Http Server requests.
 * Please refer to the sample below for usage.
 *
 * $loop = Factory::create();
 * $blackfire = new \Blackfire\Bridge\ReactHttpServerHelper();
 *
 * $server = new Server(function (ServerRequestInterface $request) use ($blackfire) {
 *     $blackfire->start($request);
 *
 *     // The business logic goes here...
 *
 *     return new Response(
 *         200,
 *         array_merge(array(
 *             'Content-Type' => 'text/plain'
 *         ), $blackfire->stop()),
 *         "Hello world\n"
 *     );
 * });
 *
 * $socket = new \React\Socket\Server(isset($argv[1]) ? $argv[1] : '0.0.0.0:0', $loop);
 * $server->listen($socket);
 *
 * echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
 *
 * $loop->run();
 */
class ReactHttpServerHelper
{
    private $probe;

    public function start(ServerRequestInterface $request)
    {
        $headers = array_change_key_case($request->getHeaders(), CASE_LOWER);

        // Only enable when the X-Blackfire-Query header is present
        if (!isset($headers['x-blackfire-query'])) {
            return false;
        }

        if (null !== $this->probe) {
            return false;
        }

        $this->probe = new \BlackfireProbe($headers['x-blackfire-query'][0]);

        // Stop if it failed
        if (!$this->probe->enable()) {
            return false;
        }

        return true;
    }

    public function stop()
    {
        if (null === $this->probe) {
            return array();
        }

        if (!$this->probe->isEnabled()) {
            return array();
        }

        $this->probe->close();

        $header = explode(':', $this->probe->getResponseLine(), 2);
        $this->probe = null;

        return array('x-'.$header[0] => $header[1]);
    }
}
