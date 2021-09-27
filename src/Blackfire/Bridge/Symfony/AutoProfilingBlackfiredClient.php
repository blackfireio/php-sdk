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

use Blackfire\Profile\Configuration;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Decorates BlackfiredHttpClient in order to auto-enable it.
 * Every HTTP requests going through AutoProfilingBlackfiredClient will trigger
 * a profile, except if profiling is explicitly disabled by calling disableProfiling().
 */
class AutoProfilingBlackfiredClient extends BlackfiredHttpClient
{
    private $profilingEnabled = true;

    /**
     * @var Configuration
     */
    private $profilingConfig;

    public function isProfilingEnabled(): bool
    {
        return $this->profilingEnabled;
    }

    public function enableProfiling(?Configuration $profilingConfig = null): self
    {
        $this->profilingEnabled = true;
        $this->profilingConfig = $profilingConfig;

        return $this;
    }

    public function disableProfiling(): self
    {
        $this->profilingEnabled = false;
        $this->profilingConfig = null;

        return $this;
    }

    public function request(string $method, string $url, array $options = array()): ResponseInterface
    {
        $options['extra']['blackfire'] = $this->profilingConfig ?? $this->profilingEnabled;

        $response = parent::request($method, $url, $options);
        $this->profilingConfig = null;

        return $response;
    }
}
