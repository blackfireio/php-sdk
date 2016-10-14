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

/**
 * Blackfire middleware for Guzzle.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Middleware
{
    private $handler;
    private $blackfire;

    public function __construct(BlackfireClient $blackfire, callable $handler)
    {
        $this->blackfire = $blackfire;
        $this->handler = $handler;
    }

    public static function create(BlackfireClient $blackfire)
    {
        return function (callable $handler) use ($blackfire) {
            return new self($blackfire, $handler);
        };
    }

    /**
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->handler;

        if (!$request->hasHeader('X-Blackfire-Query') && (!isset($options['blackfire']) || false === $options['blackfire'])) {
            return $fn($request, $options);
        }

        if (!$request->hasHeader('X-Blackfire-Query')) {
            if (true === $options['blackfire']) {
                $options['blackfire'] = new ProfileConfiguration();
            } elseif (!$options['blackfire'] instanceof ProfileConfiguration) {
                throw new \InvalidArgumentException('blackfire must be true or an instance of \Blackfire\Profile\Configuration.');
            }

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
            return $response;
        }

        parse_str($response->getHeader('X-Blackfire-Response')[0], $values);
        if (!isset($values['continue']) || 'true' !== $values['continue']) {
            return $response;
        }

        Psr7\rewind_body($request);

        /* @var PromiseInterface|ResponseInterface $promise */
        return $this($request, $options);
    }
}
