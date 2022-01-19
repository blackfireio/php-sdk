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

use Blackfire\Build\BuildHelper;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

abstract class BlackfireTestCase extends TestCase
{
    /** @var string */
    protected $blackfireScenarioTitle = null;

    /** @var bool */
    protected $profileAllRequests = true;

    /** @var bool */
    protected $profileNextRequest = true;

    /** @var Request */
    private $request;

    /** @var ?string */
    private $nextProfileTitle = null;

    /** @var ?BuildHelper */
    private $buildHelper = null;

    public static function tearDownAfterClass(): void
    {
        $buildHelper = BuildHelper::getInstance();
        $scenarioKey = debug_backtrace()[1]['object']->toString();
        if ($buildHelper->hasScenario($scenarioKey)) {
            $buildHelper->endScenario($scenarioKey);
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
        }

        return parent::artisan($command, $parameters);
    }

    protected function initializeTestEnvironment(): void
    {
        $scenarioKey = get_class(debug_backtrace()[1]['object']);
        if (!$this->buildHelper->hasScenario($scenarioKey)) {
            $this->buildHelper->createScenario(
                $this->blackfireScenarioTitle ?? $scenarioKey,
                $scenarioKey
            );
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

            $stepTitle = $this->nextProfileTitle ?? $method.' '.$uri;
            $this->blackfireStepTitle = null;
            $this->request = $this->buildHelper->createRequest($scenarioKey, $stepTitle);

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
        parent::setUp();

        $this->profileNextRequest = $this->profileAllRequests;
        $this->nextProfileTitle = null;
        $this->buildHelper = BuildHelper::getInstance();

        if (!$this->buildHelper->isEnabled()) {
            $this->profileNextRequest = false;
        }
    }

    protected function tearDown(): void
    {
        $this->request = null;
        $this->nextProfileTitle = null;

        parent::tearDown();
    }

    protected function setProfileTitle(?string $profileTitle): self
    {
        $this->nextProfileTitle = $profileTitle;

        return $this;
    }

    private function printProfileLink(string $profileUuid): void
    {
        echo "\033[01;36m  https://blackfire.io/profiles/{$profileUuid}/graph \033[0m\n";
    }
}
