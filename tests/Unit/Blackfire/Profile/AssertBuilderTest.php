<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Blackfire\Profile;

use Blackfire\Profile\AssertBuilder;
use Blackfire\Profile\AssertManager;
use PHPUnit\Framework\TestCase;

class AssertBuilderTest extends TestCase
{
    /**
     * @var string $method
     * @var string $expectedResult
     *
     * @dataProvider assertsProvider
     */
    public function testAssertBuilder($method, $expectedResult)
    {
        $manager = new AssertManager();
        $builder = new AssertBuilder($manager);

        $builder->$method(1, 'test');

        $assertions = $manager->getAssertions();

        $assert = current($assertions);

        self::assertEquals($expectedResult, $assert);
    }

    /**
     * @return string[][]
     */
    public function assertsProvider()
    {
        return [
            //test SQL
            'Test sql queries count greater than or equal' => ['sqlQueriesCountGreaterThanOrEqual', 'metrics.sql.queries.count >= 1'],
            'Test sql queries count less than or equal' => ['sqlQueriesCountLessThanOrEqual', 'metrics.sql.queries.count <= 1'],
            'Test sql queries count greater than' => ['sqlQueriesCountGreaterThan', 'metrics.sql.queries.count > 1'],
            'Test sql queries count less than' => ['sqlQueriesCountLessThan', 'metrics.sql.queries.count < 1'],
            'Test sql queries count not equals' => ['sqlQueriesCountNotEquals', 'metrics.sql.queries.count != 1'],
            'Test sql sql queries count equals' => ['sqlQueriesCountEquals', 'metrics.sql.queries.count == 1'],
            // test HttpRequests
            'Test http requests count greater than or equal' => ['httpRequestsCountGreaterThanOrEqual', 'metrics.http.requests.count >= 1'],
            'Test http requests count less than or equal' => ['httpRequestsCountLessThanOrEqual', 'metrics.http.requests.count <= 1'],
            'Test http requests count greater than' => ['httpRequestsCountGreaterThan', 'metrics.http.requests.count > 1'],
            'Test http requests count less than' => ['httpRequestsCountLessThan', 'metrics.http.requests.count < 1'],
            'Test http requests count not equals' => ['httpRequestsCountNotEquals', 'metrics.http.requests.count != 1'],
            'Test http requests countcount equals' => ['httpRequestsCountEquals', 'metrics.http.requests.count == 1'],
        ];
    }

    public function testCannotResolveMethod()
    {
        $manager = new AssertManager();
        $builder = new AssertBuilder($manager);

        $this->expectException(\RuntimeException::class);

        $builder->nonExistentMethod();
    }

    public function testMethodIsNotFound()
    {
        $manager = new AssertManager();
        $builder = new AssertBuilder($manager);

        $this->expectException(\RuntimeException::class);

        $builder->nonExistentNotEquals();
    }
}
