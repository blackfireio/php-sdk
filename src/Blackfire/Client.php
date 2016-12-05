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

use Blackfire\Bridge\PhpUnit\TestConstraint as BlackfireConstraint;
use Blackfire\Exception\ApiException;
use Blackfire\Exception\EnvNotFoundException;
use Blackfire\Exception\ReferenceNotFoundException;
use Blackfire\Exception\OfflineException;
use Blackfire\Profile\Configuration as ProfileConfiguration;
use Composer\CaBundle\CaBundle;

/**
 * The Blackfire Client.
 */
class Client
{
    const MAX_RETRY = 60;
    const NO_REFERENCE_ID = '00000000-0000-0000-0000-000000000000';

    private $config;
    private $collabTokens;

    public function __construct(ClientConfiguration $config = null)
    {
        if (null === $config) {
            $config = new ClientConfiguration();
        }

        $this->config = $config;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Creates a Blackfire probe.
     *
     * @return Probe
     */
    public function createProbe(ProfileConfiguration $config = null, $enable = true)
    {
        if (null === $config) {
            $config = new ProfileConfiguration();
        }

        $probe = new Probe($this->doCreateRequest($config));

        if ($enable) {
            $probe->enable();
        }

        return $probe;
    }

    /**
     * Ends a Blackfire probe.
     *
     * @return Profile
     */
    public function endProbe(Probe $probe)
    {
        $probe->close();

        $profile = $this->getProfile($probe->getRequest()->getUuid());

        $this->storeMetadata($probe->getRequest());

        return $profile;
    }

    /**
     * Creates a Blackfire Build.
     *
     * @param string|null $env     The environment name (or null to use the one configured on the client)
     * @param array       $options An array of Build options
     *                             (title, metadata, trigger_name, external_id, external_parent_id)
     *
     * @return Build
     */
    public function createBuild($env = null, $options = array())
    {
        // BC layer
        if (!is_array($options)) {
            $options = array('title' => $options);
            if (func_get_args() >= 3) {
                $options['trigger_name'] = func_get_arg(2);
            }
            if (func_get_args() >= 4) {
                $options['metadata'] = (array) func_get_arg(3);
            }
        }

        $env = $this->getEnvUuid(null === $env ? $this->config->getEnv() : $env);
        $content = json_encode($options);
        $data = json_decode($this->sendHttpRequest($this->config->getEndpoint().'/api/v1/build/env/'.$env, 'POST', array('content' => $content), array('Content-Type: application/json')), true);

        return new Build($env, $data);
    }

    /**
     * Closes a build.
     *
     * @return Report
     */
    public function endBuild(Build $build)
    {
        $uuid = $build->getUuid();

        $content = json_encode(array('nb_jobs' => $build->getJobCount()));
        $this->sendHttpRequest($this->config->getEndpoint().'/api/v1/build/'.$uuid, 'PUT', array('content' => $content), array('Content-Type: application/json'));

        return $this->getReport($uuid);
    }

    /**
     * Profiles the callback and test the result against the given configuration.
     *
     * @deprecated since 1.4, to be removed in 2.0
     */
    public function assertPhpUnit(\PHPUnit_Framework_TestCase $testCase, ProfileConfiguration $config, $callback)
    {
        if (!$config->hasMetadata('skip_timeline')) {
            $config->setMetadata('skip_timeline', 'true');
        }

        try {
            $probe = $this->createProbe($config);

            $callback();

            $profile = $this->endProbe($probe);

            $testCase->assertThat($profile, new BlackfireConstraint());
        } catch (Exception\ExceptionInterface $e) {
            $testCase->markTestSkipped($e->getMessage());
        }

        return $profile;
    }

    /**
     * Returns a profile request.
     *
     * Retrieve the X-Blackfire-Query value with Request::getToken().
     *
     * @param ProfileConfiguration|string $config The profile title or a ProfileConfiguration instance
     *
     * @return Profile\Request
     */
    public function createRequest($config = null)
    {
        if (is_string($config)) {
            $cfg = new ProfileConfiguration();
            $config = $cfg->setTitle($config);
        } elseif (null === $config) {
            $config = new ProfileConfiguration();
        } elseif (!$config instanceof ProfileConfiguration) {
            throw new \InvalidArgumentException(sprintf('The "%s" method takes a string or a Blackfire\Profile\Configuration instance.', __METHOD__));
        }

        return $this->doCreateRequest($config);
    }

    /**
     * @return bool True if the profile was successfully updated
     */
    public function updateProfile($uuid, $title)
    {
        try {
            // be sure that the profile exist first
            $this->getProfile($uuid)->getUrl();

            $this->sendHttpRequest($this->config->getEndpoint().'/api/v1/profiles/'.$uuid, 'PUT', array('content' => http_build_query(array('label' => $title), '', '&')), array('Content-Type: application/x-www-form-urlencoded'));

            return true;
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * @param string $uuid A Profile UUID
     *
     * @return Profile
     */
    public function getProfile($uuid)
    {
        $self = $this;

        return new Profile(function () use ($self, $uuid) {
            return $self->doGetProfile($uuid);
        });
    }

    /**
     * @param ProfileConfiguration $config
     * @param Build                $build
     */
    public function addJobInBuild(ProfileConfiguration $config, Build $build)
    {
        $body = $config->getRequestInfo();

        $body['name'] = $config->getTitle();

        if ($config->getUuid()) {
            $body['profile_uuid'] = $config->getUuid();
        }

        $content = json_encode($body);

        return json_decode($this->sendHttpRequest($this->config->getEndpoint().'/api/v1/build/'.$build->getUuid().'/jobs', 'POST', array('content' => $content), array('Content-Type: application/json')), true);
    }

    /**
     * @internal
     */
    public function doGetProfile($uuid)
    {
        $retry = 0;
        $e = null;
        while (true) {
            try {
                $data = json_decode($this->sendHttpRequest($this->config->getEndpoint().'/api/v1/profiles/'.$uuid), true);

                if ('finished' == $data['status']['name']) {
                    return $data;
                }

                if ('failure' == $data['status']['name']) {
                    throw new ApiException($data['status']['failure_reason']);
                }
            } catch (ApiException $e) {
                if (404 != $e->getCode() || $retry > self::MAX_RETRY) {
                    throw $e;
                }
            }

            usleep(++$retry * 50000);

            if ($retry > self::MAX_RETRY) {
                if (null === $e) {
                    throw new ApiException('Profile is still in the queue.');
                }

                throw ApiException::fromStatusCode('Unknown error from the API.', $e->getCode(), $e);
            }
        }
    }

    /**
     * @param string $uuid A Report UUID
     *
     * @return Report
     */
    public function getReport($uuid)
    {
        $self = $this;

        return new Report(function () use ($self, $uuid) {
            return $self->doGetReport($uuid);
        });
    }

    /**
     * @internal
     */
    public function doGetReport($uuid)
    {
        $retry = 0;
        $e = null;
        while (true) {
            try {
                $data = json_decode($this->sendHttpRequest($this->config->getEndpoint().'/api/v1/build/'.$uuid), true);

                if ('finished' === $data['status']['name']) {
                    return $data;
                }

                if ('errored' === $data['status']['name']) {
                    throw new ApiException($data['status']['failure_reason'] ? $data['status']['failure_reason'] : 'Build errored.');
                }
            } catch (ApiException $e) {
                if (404 != $e->getCode() || $retry > self::MAX_RETRY) {
                    throw $e;
                }
            }

            usleep(++$retry * 50000);

            if ($retry > self::MAX_RETRY) {
                if (null === $e) {
                    throw new ApiException('Report is still in the queue.');
                }

                throw ApiException::fromStatusCode('Unknown error from the API.', $e->getCode(), $e);
            }
        }
    }

    private function doCreateRequest(ProfileConfiguration $config)
    {
        $content = json_encode($details = $this->getRequestDetails($config));
        $data = json_decode($this->sendHttpRequest($this->config->getEndpoint().'/api/v1/signing', 'POST', array('content' => $content), array('Content-Type: application/json')), true);

        $request = new Profile\Request($config, $data);

        if ($config->getReference() && $config->isNewReference()) {
            // promote the profile as being the new reference
            $content = json_encode(array('request_id' => $request->getUuid(), 'slot_id' => $details['profileSlot']));
            $this->sendHttpRequest($this->config->getEndpoint().'/api/v1/profiles/'.$request->getUuid().'/promote-reference', 'POST', array('content' => $content), array('Content-Type: application/json'));
        }

        return $request;
    }

    private function getCollabTokens()
    {
        return json_decode($this->sendHttpRequest($this->config->getEndpoint().'/api/v1/collab-tokens'), true);
    }

    private function getEnvUuid($env)
    {
        $env = $this->getEnvDetails($env);

        return $env['collabToken'];
    }

    private function getEnvDetails($env)
    {
        if (null === $this->collabTokens) {
            $this->collabTokens = $this->getCollabTokens();
        }

        if (!$env) {
            return $this->collabTokens['collabTokens'][0];
        }

        foreach ($this->collabTokens['collabTokens'] as $i => $collabToken) {
            if (isset($collabToken['search_identifiers']) && in_array($env, $collabToken['search_identifiers'])) {
                return $collabToken;
            }

            if (isset($collabToken['collabToken']) && $collabToken['collabToken'] == $env) {
                return $collabToken;
            }

            if (isset($collabToken['name']) && false !== stripos($collabToken['name'], $env)) {
                return $collabToken;
            }
        }

        throw new EnvNotFoundException(sprintf('Environment "%s" does not exist.', $env));
    }

    private function getRequestDetails(ProfileConfiguration $config)
    {
        $details = array();
        $build = $config->getBuild();
        $envDetails = $this->getEnvDetails($build ? $build->getEnv() : $this->config->getEnv());

        if ($build) {
            $details['collabToken'] = $build->getEnv();

            $data = $this->addJobInBuild($config, $build);

            $build->incJob();

            $details['requestId'] = $data['uuid'];
        } else {
            $details['collabToken'] = $envDetails['collabToken'];
        }

        $id = self::NO_REFERENCE_ID;
        if ($config->getReference() || $config->isNewReference()) {
            foreach ($envDetails['profileSlots'] as $profileSlot) {
                if ($config->isNewReference()) {
                    if ($profileSlot['empty'] && self::NO_REFERENCE_ID !== $profileSlot['id']) {
                        $id = $profileSlot['id'];

                        break;
                    }
                } elseif ($config->getReference() == $profileSlot['number'] || $config->getReference() == $profileSlot['id']) {
                    $id = $profileSlot['id'];

                    break;
                }
            }

            if (self::NO_REFERENCE_ID === $id) {
                if ($config->isNewReference()) {
                    throw new ReferenceNotFoundException('Unable to create a new reference, your reference quota is reached');
                } else {
                    throw new ReferenceNotFoundException(sprintf('Unable to find the "%s" reference.', $config->getReference()));
                }
            }
        }

        $details['profileSlot'] = $id;

        return $details;
    }

    private function storeMetadata(Profile\Request $request)
    {
        if (!$request->getUserMetadata()) {
            return;
        }

        return json_decode($this->sendHttpRequest($request->getStoreUrl(), 'POST', array('content' => json_encode($request->getUserMetadata())), array('Content-Type: application/json')), true);
    }

    private function sendHttpRequest($url, $method = 'GET', $context = array(), $headers = array())
    {
        $headers[] = 'Authorization: Basic '.base64_encode($this->config->getClientId().':'.$this->config->getClientToken());
        $headers[] = 'X-Blackfire-User-Agent: Blackfire PHP SDK/1.0';

        $caPath = CaBundle::getSystemCaRootBundlePath();
        $sslOpts = array(
            'verify_peer' => 1,
            'verify_host' => 2,
        );

        if (is_dir($caPath)) {
            $sslOpts['capath'] = $caPath;
        } else {
            $sslOpts['cafile'] = $caPath;
        }

        $context = stream_context_create(array(
            'http' => array_replace(array(
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'follow_location' => true,
                'max_redirects' => 3,
                'timeout' => 60,
            ), $context),
            'ssl' => $sslOpts,
        ));

        set_error_handler(function ($type, $message) {
            throw new OfflineException(sprintf('An error occurred: %s.', $message));
        });
        try {
            $body = file_get_contents($url, 0, $context);
        } catch (\Exception $e) {
            restore_error_handler();

            throw $e;
        }
        restore_error_handler();

        if (!$data = @json_decode($body, true)) {
            $data = array('message' => '');
        }

        $error = isset($data['message']) ? $data['message'] : 'Unknown error';

        // status code
        if (!preg_match('{HTTP/\d\.\d (\d+) }i', $http_response_header[0], $match)) {
            throw ApiException::fromURL($method, $url, sprintf('An unknown API error occurred (%s).', $error), null, $context, $headers);
        }

        $statusCode = $match[1];

        if ($statusCode >= 401) {
            throw ApiException::fromURL($method, $url, $error, $statusCode, $context, $headers);
        }

        if ($statusCode >= 300) {
            throw ApiException::fromURL($method, $url, sprintf('The API call failed for an unknown reason (HTTP %d: %s).', $statusCode, $error), $statusCode, $context, $headers);
        }

        return $body;
    }
}
