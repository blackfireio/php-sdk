<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire;

class ClientConfiguration
{
    private $configResolved = false;
    private $config;
    private $clientId;
    private $clientToken;
    private $env;
    private $endpoint;

    public function __construct($clientId = null, $clientToken = null, $env = null)
    {
        $this->clientId = $clientId;
        $this->clientToken = $clientToken;
        $this->env = $env;
    }

    public static function createFromFile($file)
    {
        if (!file_exists($file)) {
            throw new Exception\ConfigNotFoundException(sprintf('Configuration file "%s" does not exist.', $file));
        }

        $config = new self();
        $config->config = $file;

        return $config;
    }

    /**
     * @return $this
     */
    public function setEnv($env)
    {
        $this->env = $env;
    }

    public function getEnv()
    {
        return $this->env;
    }

    /**
     * @return $this
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        $this->configResolved = false;
    }

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
    }

    public function getClientToken()
    {
        if (!$this->configResolved) {
            $this->resolveConfig();
        }

        return $this->clientToken;
    }

    /**
     * @return $this
     */
    public function setEndPoint($endPoint)
    {
        $this->endpoint = $endPoint;
        $this->configResolved = false;
    }

    public function getEndPoint()
    {
        if (!$this->configResolved) {
            $this->resolveConfig();
        }

        return $this->endpoint;
    }

    private function resolveConfig()
    {
        $this->resolveConfig = true;

        $config = null;
        if ($this->config) {
            $config = parse_ini_file($this->config);
        } else {
            $home = $this->getHomeDir();
            if ($home && file_exists($home.'/.blackfire.ini')) {
                $config = parse_ini_file($home.'/.blackfire.ini');
            }
        }

        if (null !== $config) {
            if (null === $this->clientId) {
                $this->clientId = $config['client-id'];
            }
            if (null === $this->clientToken) {
                $this->clientToken = $config['client-token'];
            }
            if (null === $this->endpoint) {
                $this->endpoint = rtrim($config['endpoint'], '/');
            }
        }

        if (null === $this->endpoint) {
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
    }
}
