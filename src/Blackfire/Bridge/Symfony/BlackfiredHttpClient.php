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

use Blackfire\Client as BlackfireClient;
use Blackfire\Profile\Configuration as ProfileConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class BlackfiredHttpClient implements HttpClientInterface
{
    use HttpClientTrait;

    private $client;
    private $blackfire;
    private $logger;
    private $autoEnable;

    public function __construct(HttpClientInterface $client, BlackfireClient $blackfire, LoggerInterface $logger = null, bool $autoEnable = true)
    {
        $this->client = $client;
        $this->blackfire = $blackfire;
        $this->logger = $logger;
        $this->autoEnable = $autoEnable;
    }

    public function request(string $method, string $url, array $options = array()): ResponseInterface
    {
        // this normalizes HTTP headers and allows direct access to $options['headers']['x-blackfire-query']
        // without checking the header name case sensitivity
        [, $options] = self::prepareRequest($method, $url, $options, static::OPTIONS_DEFAULTS);

        if ($this->shouldAutoEnable() && !isset($options['extra']['blackfire'])) {
            $options['extra']['blackfire'] = new ProfileConfiguration();
        }

        if (!isset($options['headers']['x-blackfire-query']) && (!isset($options['extra']['blackfire']) || false === $options['extra']['blackfire'])) {
            return $this->client->request($method, $url, $options);
        }

        if (!isset($options['headers']['x-blackfire-query'])) {
            if (\BlackfireProbe::isEnabled()) {
                $probe = \BlackfireProbe::getMainInstance();
                $probe->disable();
            }

            if (isset($options['extra']['blackfire']) && true === $options['extra']['blackfire']) {
                $options['extra']['blackfire'] = new ProfileConfiguration();
            } elseif (!(($options['extra']['blackfire'] ?? null) instanceof ProfileConfiguration)) {
                throw new \InvalidArgumentException('blackfire must be true or an instance of \Blackfire\Profile\Configuration.');
            }

            $profileRequest = $this->blackfire->createRequest($options['extra']['blackfire']);

            if (isset($probe)) {
                $probe->enable();
            }

            $options['headers']['X-Blackfire-Query'] = $profileRequest->getToken();
            $options['headers']['X-Blackfire-Profile-Url'] = $profileRequest->getProfileUrl();
            $options['headers']['X-Blackfire-Profile-Uuid'] = $profileRequest->getUuid();
        }

        $response = $this->client->request($method, $url, $options);

        return $this->processResponse($method, $url, $options, $response);
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    private function processResponse($method, $url, array $options, ResponseInterface $response)
    {
        $headers = $response->getHeaders(false);

        if (!isset($headers['x-blackfire-response'])) {
            if (null !== $this->logger) {
                $this->logger->warning('Profile request failed.', array(
                    'profile-uuid' => $headers['x-blackfire-profile-uuid'] ?? null,
                    'profile-url' => $headers['x-blackfire-profile-url'] ?? null,
                ));
            }

            return $response;
        }

        parse_str($headers['x-blackfire-response'][0], $values);

        if (!isset($values['continue']) || 'true' !== $values['continue']) {
            if (null !== $this->logger) {
                $this->logger->debug('Profile request succeeded.', array(
                    'profile-uuid' => $headers['x-blackfire-profile-uuid'] ?? null,
                    'profile-url' => $headers['x-blackfire-profile-url'] ?? null,
                ));
            }

            return $response;
        }

        return $this->request($method, $url, $options);
    }

    private function shouldAutoEnable(): bool
    {
        if (\BlackfireProbe::isEnabled() && $this->autoEnable) {
            if (isset($_SERVER['HTTP_X_BLACKFIRE_QUERY'])) {
                // Let's disable subrequest profiling if aggregation is enabled
                if (preg_match('/aggreg_samples=(\d+)/', $_SERVER['HTTP_X_BLACKFIRE_QUERY'], $matches)) {
                    return '1' === $matches[1];
                }
            }
        }

        return false;
    }
}
