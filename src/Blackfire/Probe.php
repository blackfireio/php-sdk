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

use Blackfire\Exception\ApiException;
use Blackfire\Profile\Request;

/**
 * Represents a Blackfire Probe.
 *
 * Instances of this class should never be created directly.
 * Use Blackfire\Client instead.
 */
class Probe
{
    private $probe;
    private $request;

    /**
     * @internal
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->probe = new \BlackfireProbe($request->getToken());

        if ($yaml = $request->getYaml()) {
            $this->probe->setConfiguration($yaml);
        }
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function discard()
    {
        return $this->probe->discard();
    }

    /**
     * @throw ApiException if the probe cannot be enabled
     */
    public function enable()
    {
        $ret = $this->probe->enable();

        $this->checkError();

        return $ret;
    }

    public function disable()
    {
        return $this->probe->disable();
    }

    public function close()
    {
        $ret = $this->probe->close();

        $this->captureAgentUuid();

        return $ret;
    }

    /**
     * The uuid of the agent that handled the profile, decoded from the probe
     * response line (the X-Blackfire-Response payload). Null when unknown.
     */
    public function getAgentUuid()
    {
        return $this->request->getAgentUuid();
    }

    private function captureAgentUuid()
    {
        $response = $this->probe->getResponseLine();
        $prefix = 'Blackfire-Response: ';
        if (!is_string($response) || 0 !== strpos($response, $prefix)) {
            return;
        }

        parse_str(substr($response, strlen($prefix)), $values);
        if (!empty($values['agent_uuid'])) {
            $this->request->setAgentUuid($values['agent_uuid']);
        }
    }

    private function checkError()
    {
        $response = $this->probe->getResponseLine();
        $errorPrefix = 'Blackfire-Error: ';
        if (0 !== strpos($response, $errorPrefix)) {
            return;
        }

        // 4 is the length of the error code + one space
        $error = substr($response, strlen($errorPrefix) + 4);
        $code = substr($response, strlen($errorPrefix), 3);

        throw ApiException::fromStatusCode($error, $code);
    }
}
