<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Profile\Assertion;

class AssertionsBuilder
{
    /** @var string */
    const NOT_EQUALS_OPERATOR = '!=';

    /** @var string */
    const EQUALS_OPERATOR = '==';

    /** @var string */
    const LESS_THAN_OPERATOR = '<';

    /** @var string */
    const GREATER_THAN_OPERATOR = '>';

    /** @var string */
    const GREATER_THAN_OR_EQUAL_OPERATOR = '>=';

    /** @var string */
    const LESS_THAN_OR_EQUAL_OPERATOR = '<=';

    /**
     * @var AssertionsCollection
     */
    private $assertionsCollection;

    /**
     * @var AssertionsCollection $assertionsCollection
     */
    public function __construct(AssertionsCollection $assertionsCollection)
    {
        $this->assertionsCollection = $assertionsCollection;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountGreaterThanOrEqual($expected, $name = null)
    {
        $this->addSqlQueriesCountAssertion(self::GREATER_THAN_OR_EQUAL_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountLessThanOrEqual($expected, $name = null)
    {
        $this->addSqlQueriesCountAssertion(self::LESS_THAN_OR_EQUAL_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountGreaterThan($expected, $name = null)
    {
        $this->addSqlQueriesCountAssertion(self::GREATER_THAN_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountLessThan($expected, $name = null)
    {
        $this->addSqlQueriesCountAssertion(self::LESS_THAN_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountNotEquals($expected, $name = null)
    {
        $this->addSqlQueriesCountAssertion(self::NOT_EQUALS_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountEquals($expected, $name = null)
    {
        $this->addSqlQueriesCountAssertion(self::EQUALS_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountGreaterThanOrEqual($expected, $name = null)
    {
        $this->addHttpRequestsCountAssertion(self::GREATER_THAN_OR_EQUAL_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountLessThanOrEqual($expected, $name = null)
    {
        $this->addHttpRequestsCountAssertion(self::LESS_THAN_OR_EQUAL_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountGreaterThan($expected, $name = null)
    {
        $this->addHttpRequestsCountAssertion(self::GREATER_THAN_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountLessThan($expected, $name = null)
    {
        $this->addHttpRequestsCountAssertion(self::LESS_THAN_OPERATOR, $expected, $name);

        return $this;
    }

    /**
     * @param int $expected
     * @param string|null $name
     */
    public function httpRequestsCountNotEquals($expected, $name = null)
    {
        $this->addHttpRequestsCountAssertion(self::NOT_EQUALS_OPERATOR, $expected, $name);
    }

    /**
     * @param int $expected
     * @param string|null $name
     */
    public function httpRequestsCountEquals($expected, $name = null)
    {
        $this->addHttpRequestsCountAssertion(self::EQUALS_OPERATOR, $expected, $name);
    }

    /**
     * @param string $comparison
     * @param int $expected
     * @param null $name
     */
    private function addSqlQueriesCountAssertion($comparison, $expected, $name = null)
    {
        $this->addAssertion('metrics.sql.queries.count', $comparison, $expected, $name);
    }

    /**
     * @param string $comparison
     * @param int $expected
     * @param null $name
     */
    private function addHttpRequestsCountAssertion($comparison, $expected, $name = null)
    {
        $this->addAssertion('metrics.http.requests.count', $comparison, $expected, $name);
    }

    /**
     * @param string $assertType
     * @param string $comparison
     * @param int $expected
     * @param string|null $name
     */
    private function addAssertion($assertType, $comparison, $expected, $name = null)
    {
        $this->assertionsCollection->add(
            sprintf('%s %s %d', $assertType, $comparison, $expected),
            $name
        );
    }
}
