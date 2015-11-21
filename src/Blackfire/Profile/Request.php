<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Profile;

/**
 * Represents a Blackfire Profile Request.
 *
 * Instances of this class should never be created directly.
 * Use Blackfire\Client instead.
 */
class Request
{
    private $data;

    /**
     * @internal
     */
    public function __construct(Configuration $configuration, $data)
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
        $data['yaml'] = $configuration->toYaml();

        $this->data = $data;
    }

    public function getToken()
    {
        return $this->data['query_string'].'&'.http_build_query($this->data['options']);
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

    public function getUuid()
    {
        return $this->data['uuid'];
    }

    public function getYaml()
    {
        return $this->data['yaml'];
    }
}
