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

use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Blackfire\Bridge\Behat\BlackfireExtension\Event\BuildSubscriber;
use Blackfire\Bridge\Behat\BlackfireExtension\Event\ScenarioSubscriber;
use Blackfire\Bridge\Behat\BlackfireExtension\ServiceContainer\Driver\BlackfiredHttpBrowserFactory;
use Blackfire\Bridge\Symfony\BlackfiredHttpBrowser;
use Blackfire\Build\BuildHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BlackfireExtension implements ExtensionInterface
{
    private $minkExtensionFound = false;

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
        $this->minkExtensionFound = true;
    }

    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('blackfire_environment')
                    ->isRequired()
                    ->info('The Blackfire environment name or its UUID.')
                ->end()
                ->scalarNode('build_name')
                    ->defaultValue('Behat Build')
                    ->info('Name for the build, as it appears in the Blackfire Build Dashboard.')
                ->end()
            ->end()
        ->end();
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $container->setDefinition(
            BuildHelper::class,
            (new Definition(BuildHelper::class))
                ->setFactory(BuildHelper::class.'::getInstance')
        );
        $container->setDefinition(
            BlackfiredHttpBrowser::class,
            new Definition(BlackfiredHttpBrowser::class, array(new Reference(BuildHelper::class)))
        );

        $container->setParameter('blackfire.environment', $config['blackfire_environment']);
        $container->setParameter('blackfire.build_name', $config['build_name']);

        $this->registerSubscribers($container);
    }

    private function registerSubscribers(ContainerBuilder $container)
    {
        $buildSubscriberDef = new Definition(BuildSubscriber::class, array(
            new Reference(OutputExtension::FORMATTER_TAG.'.pretty'),
            new Reference(BuildHelper::class),
            '%blackfire.environment%',
            '%blackfire.build_name%',
        ));
        $buildSubscriberDef->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);
        $container->setDefinition(
            BuildSubscriber::class,
            $buildSubscriberDef
        );

        $scenarioSubscriberDef = new Definition(ScenarioSubscriber::class, array(
            new Reference(BuildHelper::class),
            new Reference(MinkExtension::MINK_ID),
        ));
        $scenarioSubscriberDef->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);
        $container->setDefinition(ScenarioSubscriber::class, $scenarioSubscriberDef);
    }
}
