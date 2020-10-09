<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Symfony;

use Blackfire\Build\BuildHelper;
use Blackfire\Profile\Configuration;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;

class BlackfiredHttpBrowser extends HttpBrowser
{
    /**
     * @var BuildHelper
     */
    private $buildHelper;

    /**
     * @var \Blackfire\Client
     */
    private $blackfire;

    /**
     * @var AutoProfilingBlackfiredClient
     */
    private $blackfiredHttpClient;

    private $profilingEnabled;

    private $profileTitle;

    public function __construct(BuildHelper $buildHelper)
    {
        $this->buildHelper = $buildHelper;
        $this->profilingEnabled = $buildHelper->isEnabled();
        $this->blackfire = $buildHelper->getBlackfireClient();

        if (!class_exists(HttpClient::class)) {
            throw new \RuntimeException('symfony/http-client is required to use the BlackfiredHttpBrowser, please add it to your composer dependencies.');
        }
        $this->blackfiredHttpClient = new AutoProfilingBlackfiredClient(HttpClient::create(), $this->blackfire);

        parent::__construct($this->blackfiredHttpClient);
    }

    public function isProfilingEnabled(): bool
    {
        return $this->profilingEnabled;
    }

    public function enableProfiling(?string $title = null): self
    {
        $this->profilingEnabled = true;
        $this->profileTitle = $title;

        return $this;
    }

    public function disableProfiling(): self
    {
        $this->profilingEnabled = false;
        $this->profileTitle = null;

        return $this;
    }

    public function request(string $method, string $uri, array $parameters = array(), array $files = array(), array $server = array(), string $content = null, bool $changeHistory = true)
    {
        if ($this->isProfilingEnabled()) {
            $profileConfig = (new Configuration())->setTitle($this->profileTitle ?? sprintf('%s - %s', $uri, $method));
            if ($this->buildHelper->hasCurrentScenario()) {
                $profileConfig->setScenario($this->buildHelper->getCurrentScenario());
            }

            $this->blackfiredHttpClient->enableProfiling($profileConfig);
        } else {
            $this->blackfiredHttpClient->disableProfiling();
        }

        try {
            $crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->profileTitle = null;
        }

        return $crawler;
    }

    public function getResponse()
    {
        // Transform BrowserKit\Response into HttpFoundation\Response
        $response = parent::getResponse();

        return new Response($response->getContent(), $response->getStatusCode(), $response->getHeaders());
    }
}
