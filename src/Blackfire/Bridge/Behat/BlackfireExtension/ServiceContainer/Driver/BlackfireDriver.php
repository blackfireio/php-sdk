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

use Behat\Mink\Driver\BrowserKitDriver;
use Blackfire\Build\BuildHelper;
use Symfony\Component\BrowserKit\AbstractBrowser;

class BlackfireDriver extends BrowserKitDriver
{
    private $buildHelper;

    public function __construct(AbstractBrowser $client, ?string $baseUrl = null, ?BuildHelper $buildHelper = null)
    {
        parent::__construct($client, $baseUrl);

        $this->buildHelper = $buildHelper ?? BuildHelper::getInstance();
    }
}
