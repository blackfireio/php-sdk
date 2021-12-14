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

use Blackfire\Build\ParallelScenariosBuildHelper;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Request;

trait BlackfireTestCaseTrait
{
    /** @var ?string */
    protected $blackfireScenarioTitle = null;

    /** @var bool */
    protected $profileAllRequests = true;

    /** @var bool */
    protected $profileNextRequest = true;

    /** @var Request */
    private $request;

    /** @var ParallelScenariosBuildHelper */
    private static $buildHelper = null;

    public static function getBuildHelper(): ?ParallelScenariosBuildHelper
    {
        return self::$buildHelper;
    }

    public static function tearDownAfterClass(): void
    {
        $buildHelper = self::getBuildHelper();
        if (!$buildHelper) {
            return;
        }

        $scenarioKey = debug_backtrace()[1]['object']->toString();
        if (self::getBuildHelper()->hasScenario($scenarioKey)) {
            self::getBuildHelper()->closeScenario($scenarioKey);
        }
    }

    /**
     * Call artisan command and return code.
     *
     * @param string $command
     * @param array  $parameters
     *
     * @return \Illuminate\Testing\PendingCommand|int
     */
    public function artisan($command, $parameters = array())
    {
        if ($this->profileNextRequest) {
            $parameters['blackfire-laravel-tests'] = true;
            $output = parent::artisan($command, $parameters);
        }

        return $output;
    }

    protected function initializeTestEnvironment(): void
    {
        if (!self::$buildHelper) {
            self::$buildHelper = new ParallelScenariosBuildHelper();
        }

        if (!self::$buildHelper->hasCurrentBuild()) {
            self::$buildHelper->startBuild(
                env('BLACKFIRE_TEST_ENVIRONMENT'),
                env('BLACKFIRE_TEST_BUILD_TITLE', 'Laravel Tests')
            );
        }

        $scenarioKey = get_class(debug_backtrace()[1]['object']);
        if (!self::$buildHelper->hasScenario($scenarioKey)) {
            self::$buildHelper->startScenario($scenarioKey, $this->blackfireScenarioTitle);
        }
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param string      $method
     * @param string      $uri
     * @param array       $parameters
     * @param array       $cookies
     * @param array       $files
     * @param array       $server
     * @param string|null $content
     *
     * @return \Illuminate\Testing\TestResponse
     */
    public function call($method, $uri, $parameters = array(), $cookies = array(), $files = array(), $server = array(), $content = null)
    {
        if ($this->profileNextRequest) {
            $this->initializeTestEnvironment();

            $scenarioKey = get_class(debug_backtrace()[1]['object']);

            $scenarioTitle = $method.' '.$uri;
            $this->request = self::$buildHelper->createRequest($scenarioKey, $scenarioTitle);

            $server[$this->formatServerHeaderKey('X-Blackfire-Query')] = $this->request->getToken();
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    protected function createTestResponse($response): TestResponse
    {
        $response = parent::createTestResponse($response);

        if ($this->request) {
            $this->printProfileLink($this->request->getUuid());
        }

        return $response;
    }

    protected function enableProfiling(): self
    {
        $this->profileNextRequest = true;

        return $this;
    }

    protected function disableProfiling(): self
    {
        $this->profileNextRequest = false;

        return $this;
    }

    protected function setUp(): void
    {
        $this->profileNextRequest = $this->profileAllRequests;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->request = null;

        parent::tearDown();
    }

    private function printProfileLink(string $profileUuid): void
    {
        echo "\033[01;36m  https://blackfire.io/profiles/{$profileUuid}/graph \033[0m\n";
    }
}
