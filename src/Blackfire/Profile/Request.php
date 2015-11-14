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

class Request
{
    private $data;
    private $probe;

    public function __construct(Configuration $configuration, $data)
    {
        $data['options']['aggreg_samples'] = $configuration->getSamples();
        if ($configuration->getTitle()) {
            $data['options']['profile_title'] = $configuration->getTitle();
        }
        $data['user_metadata'] = $configuration->getAllMetadata();

        $this->probe = new \BlackfireProbe($data['query_string'].'&'.http_build_query($data['options']));
        $this->data = $data;

        if ($yaml = $configuration->toYaml()) {
            $this->probe->setConfiguration($yaml);
        }
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
