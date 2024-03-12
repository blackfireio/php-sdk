<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Behat\BlackfireExtension\ServiceContainer\Driver;

use Blackfire\Bridge\Symfony\BlackfiredKernelBrowser;

class BlackfireKernelBrowserDriver extends BlackfireDriver
{
    private $client;

    private $baseUrl;

    public function __construct(BlackfiredKernelBrowser $client, ?string $baseUrl = null)
    {
        parent::__construct($client, $baseUrl);

        $client->enableBlackfire();
        $this->baseUrl = $baseUrl;
        $this->client = $client;
    }

    public function reset()
    {
        parent::reset();

        parent::__construct($this->client, $this->baseUrl);
    }
}
