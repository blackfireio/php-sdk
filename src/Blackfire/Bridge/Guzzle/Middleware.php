<?php

namespace Blackfire\Bridge\Guzzle;

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Blackfire\Client as BlackfireClient;
use Blackfire\Profile\Configuration as ProfileConfiguration;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Blackfire middleware for Guzzle.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Middleware
{
    private $handler;
    private $blackfire;
    private $logger;

    public function __construct(BlackfireClient $blackfire, callable $handler, LoggerInterface $logger = null)
    {
        $this->blackfire = $blackfire;
        $this->handler = $handler;
        $this->logger = $logger;
    }

    public static function create(BlackfireClient $blackfire, LoggerInterface $logger = null)
    {
        return function (callable $handler) use ($blackfire, $logger) {
            return new self($blackfire, $handler, $logger);
        };
    }

    /**
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->handler;

        if (!isset($options['blackfire']) || false === $options['blackfire']) {
            return $fn($request, $options);
        }

        if (true === $options['blackfire']) {
            $options['blackfire'] = new ProfileConfiguration();
        } elseif (!$options['blackfire'] instanceof ProfileConfiguration) {
            throw new \InvalidArgumentException('blackfire must be true or an instance of \Blackfire\Profile\Configuration.');
        }

        if (!$request->hasHeader('X-Blackfire-Query')) {
            $profileRequest = $this->blackfire->createRequest($options['blackfire']);

            $request = $request
                ->withHeader('X-Blackfire-Query', $profileRequest->getToken())
                ->withHeader('X-Blackfire-Profile-Uuid', $profileRequest->getUuid())
            ;
        }

        return $fn($request, $options)
            ->then(function (ResponseInterface $response) use ($request, $options) {
                return $this->processResponse($request, $options, $response);
            });
    }

    /**
     * @param RequestInterface                   $request
     * @param array                              $options
     * @param ResponseInterface|PromiseInterface $response
     *
     * @return ResponseInterface|PromiseInterface
     */
    public function processResponse(RequestInterface $request, array $options, ResponseInterface $response)
    {
        $response = $response->withHeader('X-Blackfire-Profile-Uuid', $request->getHeader('X-Blackfire-Profile-Uuid'));

        if (!$response->hasHeader('X-Blackfire-Response')) {
            if (null !== $this->logger) {
                $this->logger->warning('Profile request seems to have failed', array(
                    'profile-uuid' => $request->getHeader('X-Blackfire-Profile-Uuid'),
                    'profile-url' => $request->getHeader('X-Blackfire-Profile-Url'),
                ));
            }

            return $response;
        }

        parse_str($response->getHeader('X-Blackfire-Response')[0], $values);

        if (!isset($values['continue']) || 'true' !== $values['continue']) {
            if (null !== $this->logger) {
                $this->logger->debug('Profile request succeeded', array(
                    'profile-uuid' => $request->getHeader('X-Blackfire-Profile-Uuid'),
                    'profile-url' => $request->getHeader('X-Blackfire-Profile'),
                ));
            }

            return $response;
        }

        Psr7\rewind_body($request);

        /* @var PromiseInterface|ResponseInterface $promise */
        return $this($request, $options);
    }
}
