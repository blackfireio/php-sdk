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
use Behat\MinkExtension\ServiceContainer\Driver\DriverFactory;
use Blackfire\Bridge\Symfony\BlackfiredKernelBrowser;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BlackfiredKernelBrowserFactory implements DriverFactory
{
    public function getDriverName()
    {
        return 'blackfire_symfony';
    }

    public function supportsJavascript()
    {
        return false;
    }

    public function configure(ArrayNodeDefinition $builder)
    {
    }

    public function buildDriver(array $config)
    {
        if (!class_exists(BrowserKitDriver::class)) {
            throw new \RuntimeException('Install "friends-of-behat/mink-browserkit-driver" (drop-in replacement for "behat/mink-browserkit-driver") in order to use the "symfony" driver.');
        }

        return new Definition(BlackfireKernelBrowserDriver::class, array(
            new Reference(BlackfiredKernelBrowser::class),
            '%mink.base_url%',
        ));
    }
}
