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
 * @method self sqlQueriesCountGreaterThanOrEqual(int $expected, string|null $name = null)
 * @method self sqlQueriesCountLessThanOrEqual(int $expected, string|null $name = null)
 * @method self sqlQueriesCountGreaterThan(int $expected, string|null $name = null)
 * @method self sqlQueriesCountLessThan(int $expected, string|null $name = null)
 * @method self sqlQueriesCountNotEquals(int $expected, string|null $name = null)
 * @method self sqlQueriesCountEquals(int $expected, string|null $name = null)
 *
 * @method self httpRequestsCountGreaterThanOrEqual(int $expected, string|null $name = null)
 * @method self httpRequestsCountLessThanOrEqual(int $expected, string|null $name = null)
 * @method self httpRequestsCountGreaterThan(int $expected, string|null $name = null)
 * @method self httpRequestsCountLessThan(int $expected, string|null $name = null)
 * @method self httpRequestsCountNotEquals(int $expected, string|null $name = null)
 * @method self httpRequestsCountEquals(int $expected, string|null $name = null)
 */
class AssertBuilder
{
    /**
     * @var array<string, string>
     */
    const COMPARISON_OPERATORS_MAP = [
        'NotEquals' => '!=',
        'Equals' => '==',
        'LessThan' => '<',
        'GreaterThan' => '>',
        'GreaterThanOrEqual' => '>=',
        'LessThanOrEqual' => '<=',
    ];

    /**
     * @var AssertManager
     */
    private $assertManager;

    /**
     * @var AssertManager $assertManager
     */
    public function __construct(AssertManager $assertManager)
    {
        $this->assertManager = $assertManager;
    }

    /**
     * @param string $functionName
     * @param array $arguments
     */
    public function __call($functionName, $arguments)
    {
        $methodName = '';

        foreach (self::COMPARISON_OPERATORS_MAP as $comparisonName => $comparison) {
            if (substr($functionName, -strlen($comparisonName)) === $comparisonName) {
                $methodName = str_replace($comparisonName, '', $functionName);

                break;
            }
        }

        if (!$methodName) {
            throw new \RuntimeException(sprintf('%s function can\'t be resolve in AssertBuilder class', $methodName));
        }

        $methodName = sprintf('%s%s%s', 'add', ucwords($methodName), 'Assert');

        if (!method_exists($this, $methodName)) {
            throw new \RuntimeException(sprintf('%s is not found in AssertBuilder class', $methodName));
        }

        $this->$methodName($comparison, $arguments);

        return $this;
    }

    /**
     * @param string $comparison
     * @param array $arguments
     */
    private function addSqlQueriesCountAssert($comparison, $arguments)
    {
        $this->addAssert('metrics.sql.queries.count', $comparison, $arguments);
    }

    /**
     * @param string $comparison
     * @param array $arguments
     */
    private function addHttpRequestsCountAssert($comparison, $arguments)
    {
        $this->addAssert('metrics.http.requests.count', $comparison, $arguments);
    }

    /**
     * @param string $assertType
     * @param string $comparison
     * @param array $arguments
     */
    private function addAssert($assertType, $comparison, $arguments)
    {
        list($expected, $name) = array_pad($arguments, 2, null);

        $this->assertManager->assert(
            sprintf('%s %s %d', $assertType, $comparison, $expected),
            $name
        );
    }
}
