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

class Report
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Returns the Build URL on Blackfire.io.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->data['_links']['self']['href'];
    }
}
