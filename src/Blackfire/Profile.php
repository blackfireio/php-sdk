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

use Blackfire\Profile\Cost;
use Blackfire\Profile\Test;

/**
 * Represents a Blackfire Profile.
 *
 * Instances of this class should never be created directly.
 * Use Blackfire\Client instead.
 */
class Profile
{
    private $uuid;
    private $initializeProfileCallback;
    private $data;
    private $tests;
    private $recommendations;

    /**
     * @internal
     */
    public function __construct($initializeProfileCallback, $uuid = null)
    {
        $this->uuid = $uuid;
        $this->initializeProfileCallback = $initializeProfileCallback;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        if (null === $this->uuid) {
            $this->initializeProfile();
            $this->uuid = $this->data['uuid'];
        }

        return $this->uuid;
    }

    /**
     * Returns the Profile URL on Blackfire.io.
     *
     * @return string
     */
    public function getUrl()
    {
        if (null === $this->data) {
            $this->initializeProfile();
        }

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
        if (null === $this->data) {
            $this->initializeProfile();
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
            $this->initializeProfile();
        }

        return isset($this->data['report']['state']) && 'successful' === $this->data['report']['state'];
    }

    /**
     * Returns tests associated with this profile.
     *
     * @return Test[]
     */
    public function getTests()
    {
        if (null !== $this->tests) {
            return $this->tests;
        }

        if (null === $this->data) {
            $this->initializeProfile();
        }

        if (!isset($this->data['report']['tests'])) {
            return $this->tests = array();
        }

        $this->tests = array();
        foreach ($this->data['report']['tests'] as $test) {
            $this->tests[] = new Test($test['name'], $test['state'], isset($test['failures']) ? $test['failures'] : array());
        }

        return $this->tests;
    }

    /**
     * Returns recommendations associated with this profile.
     *
     * @return Test[]
     */
    public function getRecommendations()
    {
        if (null !== $this->recommendations) {
            return $this->recommendations;
        }

        if (null === $this->data) {
            $this->initializeProfile();
        }

        if (!isset($this->data['recommendations']['tests'])) {
            return $this->recommendations = array();
        }

        $this->recommendations = array();
        foreach ($this->data['recommendations']['tests'] as $test) {
            $this->recommendations[] = new Test($test['name'], $test['state'], isset($test['failures']) ? $test['failures'] : array());
        }

        return $this->recommendations;
    }

    /**
     * Returns the main costs associated with the profile.
     *
     * @return Cost
     */
    public function getMainCost()
    {
        if (null === $this->data) {
            $this->initializeProfile();
        }

        return new Cost($this->data['envelope']);
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
     * @param string $name
     *
     * @return array An array where keys are the argument values and values are Cost instances
     */
    public function getLayer($name)
    {
        if (null === $this->data) {
            $this->initializeProfile();
        }

        if (!is_array($this->data['layers'])) {
            return array();
        }

        $arguments = array();
        foreach ($this->data['layers'] as $key => $layer) {
            if ($name !== $layer) {
                continue;
            }

            foreach ($this->data['arguments'][$key] as $value => $cost) {
                $arguments[$value] = new Cost($cost);
            }
        }

        return $arguments;
    }

    /**
     * Returns the arguments for the given metric name.
     *
     * @param string $name
     *
     * @return array An array where keys are the argument values and values are Cost instances
     */
    public function getArguments($name)
    {
        if (null === $this->data) {
            $this->initializeProfile();
        }

        if (!isset($this->data['arguments'][$name])) {
            return array();
        }

        $arguments = array();
        foreach ($this->data['arguments'][$name] as $argument => $cost) {
            $arguments[$argument] = new Cost($cost);
        }

        return $arguments;
    }

    private function initializeProfile()
    {
        $this->data = call_user_func($this->initializeProfileCallback);
    }
}
