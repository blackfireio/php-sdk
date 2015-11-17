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
 * Represents a Blackfire Profile.
 *
 * Instances of this class should never be created directly.
 * Use Blackfire\Client instead.
 */
class Profile
{
    private $data;
    private $tests;

    /**
     * @internal
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Returns the Profile URL on Blackfire.io.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->data['_links']['graph_url']['href'];
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
        return isset($this->data['report']['state']) && 'successful' === $this->data['report']['state'];
    }

    /**
     * Returns tests associated with this profile.
     *
     * @return Profile\Test[]
     */
    public function getTests()
    {
        if (null !== $this->tests) {
            return $this->tests;
        }

        if (!isset($this->data['report']['tests'])) {
            return $this->tests = array();
        }

        $this->tests = array();
        foreach ($this->data['report']['tests'] as $test) {
            $this->tests[] = new Profile\Test($test['name'], $test['state'], isset($test['failures']) ? $test['failures'] : array());
        }

        return $this->tests;
    }

    /**
     * Returns the main costs associated with the profile.
     *
     * @return Profile\Cost
     */
    public function getMainCost()
    {
        return new Profile\Cost($this->data['envelope']);
    }

    /**
     * Returns the SQL queries executed during the profile.
     *
     * @return array An array where keys are SQL queries and values are Cost instances
     */
    public function getSqls()
    {
        return $this->getLayer('sql.queries');
    }

    /**
     * Returns the HTTP requests executed during the profile.
     *
     * @return array An array where keys are HTTP requests and values are Cost instances
     */
    public function getHttpRequests()
    {
        return $this->getLayer('http.requests');
    }

    /**
     * Returns the arguments for the given layer.
     *
     * @return array An array where keys are the argument values and values are Cost instances
     */
    public function getLayer($name)
    {
        if (!is_array($this->data['layers'])) {
            return array();
        }

        $arguments = array();
        foreach ($this->data['layers'] as $key => $layer) {
            if ($name !== $layer) {
                continue;
            }

            foreach ($this->data['arguments'][$key] as $value => $cost) {
                $arguments[$value] = new Profile\Cost($cost);
            }
        }

        return $arguments;
    }

    /**
     * Returns the arguments for the given metric name.
     *
     * @return array An array where keys are the argument values and values are Cost instances
     */
    public function getArguments($name)
    {
        if (!isset($this->data['arguments'][$name])) {
            return array();
        }

        $arguments = array();
        foreach ($this->data['arguments'][$name] as $argument => $cost) {
            $arguments[$argument] = new Profile\Cost($cost);
        }

        return $arguments;
    }
}
