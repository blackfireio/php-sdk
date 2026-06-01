<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Behat\BlackfireExtension\ServiceContainer;

use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Blackfire\Bridge\Behat\BlackfireExtension\ServiceContainer\Driver\BlackfiredHttpBrowserFactory;
use Blackfire\Bridge\Behat\BlackfireExtension\ServiceContainer\Driver\BlackfiredKernelBrowserFactory;
use Blackfire\Bridge\Symfony\BlackfiredHttpBrowser;
use Blackfire\Bridge\Symfony\BlackfiredKernelBrowser;
use FriendsOfBehat\SymfonyExtension\Driver\SymfonyDriver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BlackfireExtension implements ExtensionInterface
{
    public function process(ContainerBuilder $container)
    {
    }

    public function getConfigKey()
    {
        return 'blackfire';
    }

    public function initialize(ExtensionManager $extensionManager)
    {
        /** @var MinkExtension $minkExtension */
        $minkExtension = $extensionManager->getExtension('mink');
        if (null === $minkExtension) {
            return;
        }

        $minkExtension->registerDriverFactory(new BlackfiredHttpBrowserFactory());
        $minkExtension->registerDriverFactory(new BlackfiredKernelBrowserFactory());
    }

    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('blackfire_environment')
                    ->isRequired()
                    ->info('The Blackfire environment name or its UUID.')
                ->end()
            ->end()
        ->end();
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $container->setDefinition(
            BlackfiredHttpBrowser::class,
            new Definition(BlackfiredHttpBrowser::class)
        );
        if (class_exists(SymfonyDriver::class)) {
            $container->setDefinition(
                BlackfiredKernelBrowser::class,
                new Definition(BlackfiredKernelBrowser::class, array(new Reference('fob_symfony.kernel')))
            );
        }

        $container->setParameter('blackfire.environment', $config['blackfire_environment']);
    }
}
