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

use Blackfire\Profile\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BlackfiredHttpResponse implements ResponseInterface
{
    private ResponseInterface $response;

    /** @var Request */
    private $request;

    public function __construct(ResponseInterface $response, ?Request $request = null)
    {
        $this->response = $response;
        $this->request = $request;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getHeaders(bool $throw = true): array
    {
        $headers = $this->response->getHeaders($throw);

        if (null !== $this->request) {
            $headers['X-Blackfire-Profile-Uuid'] = array($this->request->getUuid());
        }

        return $headers;
    }

    public function getContent(bool $throw = true): string
    {
        return $this->response->getContent($throw);
    }

    public function toArray(bool $throw = true): array
    {
        return $this->response->toArray($throw);
    }

    public function cancel(): void
    {
        $this->response->cancel();
    }

    public function getInfo(?string $type = null): mixed
    {
        return $this->response->getInfo($type);
    }
}
