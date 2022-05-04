<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\PhpUnit;

use Blackfire\Bridge\Symfony\BlackfiredHttpBrowser;
use Blackfire\Build\BuildHelper;
use Symfony\Component\Panther\WebTestAssertionsTrait;

trait BlackfireTestCaseTrait
{
    use WebTestAssertionsTrait;

    // Define this constant in your test case to control the scenario auto-start.
    // By default a scenario is created for each test case.
    // protected const BLACKFIRE_SCENARIO_AUTO_START = true;

    // Define this constant to give a title to the auto-started scenario.
    // protected const BLACKFIRE_SCENARIO_TITLE = null;

    public static function isBlackfireScenarioAutoStart(): bool
    {
        $autoStartConstant = static::class.'::BLACKFIRE_SCENARIO_AUTO_START';

        return defined($autoStartConstant) ? constant($autoStartConstant) : true;
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $buildHelper = BuildHelper::getInstance();
        if (self::isBlackfireScenarioAutoStart() && $buildHelper->isEnabled()) {
            $scenarioConstantName = static::class.'::BLACKFIRE_SCENARIO_TITLE';
            $buildHelper->createScenario(
                defined($scenarioConstantName) ? constant($scenarioConstantName) : null
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        $buildHelper = BuildHelper::getInstance();
        if (static::isBlackfireScenarioAutoStart() && $buildHelper->isEnabled()) {
            $buildHelper->endCurrentScenario();
        }
        parent::tearDownAfterClass();
    }

    /**
     * Copycat of PanterTestCaseTrait::createHttpBrowserClient(), but using BlackfiredHttpClient.
     */
    protected static function createBlackfiredHttpBrowserClient(): BlackfiredHttpBrowser
    {
        $callGetClient = \is_callable(array(self::class, 'getClient')) && (new \ReflectionMethod(self::class, 'getClient'))->isStatic();

        static::startWebServer();

        if (null === self::$httpBrowserClient) {
            self::$httpBrowserClient = new BlackfiredHttpBrowser(BuildHelper::getInstance());
        }

        $urlComponents = parse_url(self::$baseUri);
        self::$httpBrowserClient->setServerParameter('HTTP_HOST', sprintf('%s:%s', $urlComponents['host'], $urlComponents['port']));
        if ('https' === $urlComponents['scheme']) {
            self::$httpBrowserClient->setServerParameter('HTTPS', 'true');
        }

        // Calling getClient() is mandatory in order to use assertions from BrowserKitAssertionsTrait, as this is the
        // only way to give it the created browser which will provide the testable responses.
        return $callGetClient ? self::getClient(self::$httpBrowserClient) : self::$httpBrowserClient;
    }
}
