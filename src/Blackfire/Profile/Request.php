<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
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

        if (null !== $data['yaml']) {
            if (function_exists('gzencode')) {
                $data['options']['config_yml'] = base64_encode(gzencode($data['yaml']));
            } else {
                // $data['options']['config_yml'] = base64_encode(gzencode(<<<EOYAML
                // tests:
                //   Pushing .blackfire.yml to profiled server:
                //     assertions: [ fail('The zlib extension is not installed') ]
                // EOYAML
                // ));
                $data['options']['config_yml'] = 'H4sIAAAAAAAAAxWMMQ7CQAwEe16xXUKTB+QVKdJFFBfwJVaMD90aBLyeoxzNaEIYHE/A9OSuvmFYLV2PrFWGz90QBY9asprcQKkvqf8YSGwUWpwjFuSk1nfzLviarpB3iLNJKOEloM5I1h7dGZcfq0rRYnMAAAA=';
            }
        }

        // if user has requested a debug profile
        if ($configuration->isDebug()) {
            $data['options']['aggreg_samples'] = 1;

            // and user has actually access to the debug profile
            if (isset($data['options']['no_pruning'])) {
                $data['options']['no_pruning'] = 1;
            }
            if (isset($data['options']['no_anon'])) {
                $data['options']['no_anon'] = 1;
            }
        }

        $this->data = $data;
    }

    public function getToken()
    {
        return $this->data['query_string'].'&'.http_build_query($this->data['options'], '', '&');
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
