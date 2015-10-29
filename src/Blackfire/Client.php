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

/**
 * The Blackfire Client.
 */
class Client
{
    const NO_REFERENCE_ID = '00000000-0000-0000-0000-000000000000';

    private $config;
    private $collabTokens;

    public function __construct(ClientConfiguration $config = null)
    {
        if (!class_exists('BlackfireProbe', false)) {
            throw new Exception\NotAvailableException('Blackfire is not available.');
        }

        if (null === $config) {
            $config = new ClientConfiguration();
        }

        $this->config = $config;
    }

    /**
     * Enables the Blackfire probe.
     *
     * @return Request
     */
    public function enable(Profile\Configuration $config = null, $enable = true)
    {
        if (null === $config) {
            $config = new Profile\Configuration();
        }

        $request = $this->createRequest($config);
        if ($enable) {
            $request->enable();
        }

        return $request;
    }

    /**
     * Closes the Blackfire probe.
     *
     * @return Profile
     */
    public function close(Profile\Request $request)
    {
        $request->close();

        $profile = $this->getProfile($request);

        $this->storeMetadata($request);

        return $profile;
    }

    /**
     * Profiles the callback and test the result against the given configuration.
     */
    public function assertPhpUnit(\PHPUnit_Framework_TestCase $testCase, Profile\Configuration $config, $callback)
    {
        if (!$config->hasMetadata('skip_timeline')) {
            $config->setMetadata('skip_timeline', 'true');
        }

        try {
            $request = $this->enable($config);

            $callback();

            $profile = $this->close($request);

            $testCase->assertThat($profile, new BlackfireConstraint());
        } catch (Exception\ExceptionInterface $e) {
            $testCase->markTestSkipped($e->getMessage());
        }

        return $profile;
    }

    private function createRequest(Profile\Configuration $config)
    {
        $content = json_encode($this->getRequestDetails($config));
        $data = json_decode($this->sendHttpRequest($this->config->getEndpoint().'/api/v1/signing', 'POST', array('content' => $content), array('Content-Type: application/json')), true);

        return new Profile\Request($config, $data);
    }

    private function getCollabTokens()
    {
        return json_decode($this->sendHttpRequest($this->config->getEndpoint().'/api/v1/collab-tokens'), true);
    }

    private function getRequestDetails($config)
    {
        if (null === $this->collabTokens) {
            $this->collabTokens = $this->getCollabTokens();
        }

        $ind = 0;
        if ($this->config->getApp()) {
            foreach ($this->collabTokens['collabTokens'] as $i => $collabToken) {
                if (isset($collabToken['name']) && false !== strpos(strtolower($collabToken['name']), strtolower($this->config->getApp()))) {
                    $ind = $i;
                }
            }

            if (!$ind) {
                throw new Exception\AppNotFoundException(sprintf('App "%s" does not exist.', $this->config->getApp()));
            }
        }

        $collabToken = $this->collabTokens['collabTokens'][$ind];

        $id = self::NO_REFERENCE_ID;
        if ($config->getReference() || $config->isNewReference()) {
            foreach ($collabToken['profileSlots'] as $profileSlot) {
                if ($config->isNewReference() && $profileSlot['empty'] && self::NO_REFERENCE_ID !== $profileSlot['id']) {
                    $id = $profileSlot['id'];

                    break;
                }

                if ($config->getReference() == $profileSlot['number'] || $config->getReference() == $profileSlot['id']) {
                    $id = $profileSlot['id'];

                    break;
                }
            }

            if (self::NO_REFERENCE_ID === $id) {
                throw new Exception\ReferenceNotFoundException(sprintf('Unable to find the "%s" reference.', $config->getReference()));
            }
        }

        return array('collabToken' => $collabToken['collabToken'], 'profileSlot' => $id);
    }

    private function getProfile(Profile\Request $request)
    {
        $retry = 0;
        while (true) {
            try {
                $data = json_decode($this->sendHttpRequest($request->getProfileUrl()), true);

                if ('finished' == $data['status']['name']) {
                    return new Profile($data);
                }

                if ('failure' == $data['status']['name']) {
                    throw new Exception\ApiException($data['status']['failure_reason']);
                }
            } catch (Exception\ApiException $e) {
                if (404 != $e->getCode() || $retry > 7) {
                    throw $e;
                }
            }

            usleep(++$retry * 50000);
        }
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

        $context = stream_context_create(array(
            'http' => array_replace(array(
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'follow_location' => true,
                'max_redirects' => 3,
                'timeout' => 60,
            ), $context),
            'ssl' => array(
                'verify_peer' => 1,
                'verify_host' => 2,
                'cafile' => __DIR__.'/Resources/ca-certificates.crt',
            ),
        ));

        set_error_handler(function ($type, $message) {
            throw new Exception\OfflineException(sprintf('An error occurred: %s.', $message));
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

        // status code
        if (!preg_match('{HTTP/\d\.\d (\d+) }i', $http_response_header[0], $match)) {
            throw new Exception\ApiException(sprintf('An unknown API error occurred (%s).', $data['message']));
        }

        $statusCode = $match[1];

        if ($statusCode >= 401) {
            throw new Exception\ApiException($data['message'], $statusCode);
        }

        if ($statusCode >= 300) {
            throw new Exception\ApiException(sprintf('The API call failed for an unknown reason (HTTP %d: %s).', $statusCode, $data['message']), $statusCode);
        }

        return $body;
    }
}
