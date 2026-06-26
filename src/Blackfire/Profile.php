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

        $report = self::normalizeReport(isset($this->data['report']) ? $this->data['report'] : null);

        return null !== $report && $report['errored'];
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

        $report = self::normalizeReport(isset($this->data['report']) ? $this->data['report'] : null);

        return null !== $report && $report['passing'];
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

        return $this->tests = self::buildTests(self::normalizeReport(isset($this->data['report']) ? $this->data['report'] : null));
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

        // The canonical format exposes the recommendation detail under
        // "recommendations_details" (a numeric "recommendations" count sits alongside it).
        // Legacy api.blackfire.io responses expose the detail directly under "recommendations".
        $detail = null;
        if (isset($this->data['recommendations_details'])) {
            $detail = $this->data['recommendations_details'];
        } elseif (isset($this->data['recommendations']) && is_array($this->data['recommendations'])) {
            $detail = $this->data['recommendations'];
        }

        return $this->recommendations = self::buildTests(self::normalizeReport($detail));
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

    /**
     * Reports come in two shapes depending on the endpoint that served the profile:
     *   - admin.pipeline.blackfire.io returns the canonical
     *     {empty, errored, passing, constraints} report
     *   - api.blackfire.io (legacy) returns {state, tests}
     * Normalize the legacy shape to the canonical one so the SDK only deals with one format.
     *
     * @internal
     *
     * @param array|null $report
     *
     * @return array|null
     */
    private static function normalizeReport($report)
    {
        if (!is_array($report)) {
            return null;
        }

        // Already in the canonical { constraints } format.
        if (isset($report['constraints'])) {
            return $report;
        }

        $tests = isset($report['tests']) ? $report['tests'] : array();
        $constraints = array();
        foreach ($tests as $test) {
            $assertions = array();
            foreach (isset($test['failures']) ? $test['failures'] : array() as $expression) {
                $assertions[] = array('expression' => $expression, 'passing' => false);
            }

            $constraints[] = array(
                'name' => isset($test['name']) ? $test['name'] : null,
                'errored' => isset($test['state']) && 'errored' === $test['state'],
                'passing' => isset($test['state']) && 'successful' === $test['state'],
                'assertions' => $assertions,
            );
        }

        return array(
            'empty' => 0 === count($tests),
            'errored' => isset($report['state']) && 'errored' === $report['state'],
            'passing' => isset($report['state']) && 'successful' === $report['state'],
            'constraints' => $constraints,
        );
    }

    /**
     * Builds the list of Test value objects from a canonical report.
     *
     * @internal
     *
     * @param array|null $report
     *
     * @return Test[]
     */
    private static function buildTests($report)
    {
        if (!is_array($report) || !isset($report['constraints'])) {
            return array();
        }

        $tests = array();
        foreach ($report['constraints'] as $constraint) {
            $state = 'failed';
            if (!empty($constraint['errored'])) {
                $state = 'errored';
            } elseif (!empty($constraint['passing'])) {
                $state = 'successful';
            }

            $failures = array();
            foreach (isset($constraint['assertions']) ? $constraint['assertions'] : array() as $assertion) {
                if (empty($assertion['passing']) && isset($assertion['expression'])) {
                    $failures[] = $assertion['expression'];
                }
            }

            $tests[] = new Test(isset($constraint['name']) ? $constraint['name'] : null, $state, $failures);
        }

        return $tests;
    }
}
