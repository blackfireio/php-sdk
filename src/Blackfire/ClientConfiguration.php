<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire;

use Blackfire\Exception\ConfigErrorException;
use Blackfire\Exception\ConfigNotFoundException;

class ClientConfiguration
{
    private $configResolved = false;
    private $config;
    private $clientId;
    private $clientToken;
    private $env;
    private $userAgentSuffix;
    private $endpoint;

    /**
     * @param string|null $clientId
     * @param string|null $clientToken
     * @param string|null $env
     * @param string      $userAgentSuffix
     */
    public function __construct($clientId = null, $clientToken = null, $env = null, $userAgentSuffix = '')
    {
        $this->clientId = $clientId;
        $this->clientToken = $clientToken;
        $this->env = $env;
        $this->userAgentSuffix = (string) $userAgentSuffix;
    }

    public static function createFromFile($file)
    {
        if (!file_exists($file)) {
            throw new ConfigNotFoundException(sprintf('Configuration file "%s" does not exist.', $file));
        }

        $config = new self();
        $config->config = $file;

        return $config;
    }

    /**
     * @param string|null $env
     *
     * @return $this
     */
    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * @param string $userAgentSuffix
     *
     * @return $this
     */
    public function setUserAgentSuffix($userAgentSuffix)
    {
        $this->userAgentSuffix = (string) $userAgentSuffix;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgentSuffix()
    {
        return $this->userAgentSuffix;
    }

    /**
     * @param string|null $clientId
     *
     * @return $this
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        $this->configResolved = false;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getClientId()
    {
        if (!$this->configResolved) {
            $this->resolveConfig();
        }

        return $this->clientId;
    }

    /**
     * @return $this
     */
    public function setClientToken($clientToken)
    {
        $this->clientToken = $clientToken;
        $this->configResolved = false;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getClientToken()
    {
        if (!$this->configResolved) {
            $this->resolveConfig();
        }

        return $this->clientToken;
    }

    /**
     * @param string $endPoint
     *
     * @return $this
     */
    public function setEndPoint($endPoint)
    {
        $this->endpoint = $endPoint;
        $this->configResolved = false;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndPoint()
    {
        if (!$this->configResolved) {
            $this->resolveConfig();
        }

        return $this->endpoint;
    }

    private function resolveConfig()
    {
        $this->configResolved = true;

        $config = null;
        if ($this->config) {
            $config = $this->parseConfigFile($this->config);
        } else {
            $home = $this->getHomeDir();
            if ($home && file_exists($home.'/.blackfire.ini')) {
                $config = $this->parseConfigFile($home.'/.blackfire.ini');
            }
        }

        if (null !== $config) {
            if (null === $this->clientId && isset($config['client-id'])) {
                $this->clientId = $config['client-id'];
            }
            if (null === $this->clientToken && isset($config['client-token'])) {
                $this->clientToken = $config['client-token'];
            }
            if (null === $this->endpoint && isset($config['endpoint'])) {
                $this->endpoint = rtrim($config['endpoint'], '/');
            }
        }

        if (isset($_SERVER['BLACKFIRE_CLIENT_ID'])) {
            $this->clientId = $_SERVER['BLACKFIRE_CLIENT_ID'];
        }
        if (isset($_SERVER['BLACKFIRE_CLIENT_TOKEN'])) {
            $this->clientToken = $_SERVER['BLACKFIRE_CLIENT_TOKEN'];
        }
        if (isset($_SERVER['BLACKFIRE_ENDPOINT'])) {
            $this->endpoint = rtrim($_SERVER['BLACKFIRE_ENDPOINT'], '/');
        }

        if (!$this->endpoint) {
            $this->endpoint = 'https://blackfire.io';
        }
    }

    private function getHomeDir()
    {
        if ($home = getenv('HOME')) {
            return $home;
        }

        if (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            return $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];
        }

        if ($home = getenv('USERPROFILE')) {
            // home on windows
            return $home;
        }
    }

    private function parseConfigFile($file)
    {
        if (!is_readable($file)) {
            throw new ConfigErrorException(sprintf('Unable to parse configuration file "%s": file is not readable.', $file));
        }

        if (false === $config = @parse_ini_file($file)) {
            throw new ConfigErrorException(sprintf('Unable to parse configuration file "%s".', $file));
        }

        return $config;
    }
}
