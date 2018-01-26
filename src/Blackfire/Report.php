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

class Report
{
    private $callable;
    private $data;

    /**
     * @internal
     */
    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    /**
     * Returns the Build URL on Blackfire.io.
     *
     * @return string
     */
    public function getUrl()
    {
        if (null === $this->data) {
            $this->initializeReport();
        }

        return $this->data['_links']['report']['href'];
    }

    /**
     * Returns true if the tests executed without any errors.
     *
     * Errors are different from failures. An error occurs when there is
     * a syntax error in an assertion for instance.
     *
     * @return bool
     */
    public function isErrored()
    {
        if (null === $this->data) {
            $this->initializeReport();
        }

        return isset($this->data['report']['state']) && 'errored' === $this->data['report']['state'];
    }

    /**
     * Returns true if the tests pass, false otherwise.
     *
     * You should also check isErrored() in case your tests generated an error.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        if (null === $this->data) {
            $this->initializeReport();
        }

        return isset($this->data['report']['state']) && 'successful' === $this->data['report']['state'];
    }

    private function initializeReport()
    {
        $this->data = call_user_func($this->callable);
    }
}
