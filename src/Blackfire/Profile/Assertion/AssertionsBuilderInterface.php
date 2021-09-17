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

interface AssertionsBuilderInterface
{
    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountGreaterThanOrEqual($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountLessThanOrEqual($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountGreaterThan($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountLessThan($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountNotEquals($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function sqlQueriesCountEquals($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountGreaterThanOrEqual($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountLessThanOrEqual($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountGreaterThan($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountLessThan($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountNotEquals($expected, $name = null);

    /**
     * @param int $expected
     * @param string|null $name
     *
     * @return self
     */
    public function httpRequestsCountEquals($expected, $name = null);
}
