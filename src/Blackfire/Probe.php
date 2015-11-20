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

/**
 * Represents a Blackfire Probe.
 *
 * Instances of this class should never be created directly.
 * Use Blackfire\Client instead.
 */
class Probe
{
    private $data;
    private $probe;

    /**
     * @internal
     */
    public function __construct(Profile\Configuration $configuration, $data)
    {
        if (!isset($data['query_string'])) {
            throw new \RuntimeException('The data returned by the signing API are not valid.');
        }

        if (!isset($data['options'])) {
            $data['options'] = array();
        }

        $data['options']['aggreg_samples'] = $configuration->getSamples();
        if ($configuration->getTitle()) {
            $data['options']['profile_title'] = $configuration->getTitle();
        }
        $data['user_metadata'] = $configuration->getAllMetadata();
        $this->data = $data;

        $this->probe = new \BlackfireProbe($this->getToken());

        if ($yaml = $configuration->toYaml()) {
            $this->probe->setConfiguration($yaml);
        }
    }

    public function getToken()
    {
        return $this->data['query_string'].'&'.http_build_query($this->data['options']);
    }

    public function discard()
    {
        return $this->probe->discard();
    }

    public function enable()
    {
        return $this->probe->enable();
    }

    public function disable()
    {
        return $this->probe->disable();
    }

    public function close()
    {
        return $this->probe->close();
    }

    public function getProfileUrl()
    {
        return $this->data['_links']['profile']['href'];
    }

    public function getStoreUrl()
    {
        return $this->data['_links']['store']['href'];
    }

    public function getUserMetadata()
    {
        return $this->data['user_metadata'];
    }
}
