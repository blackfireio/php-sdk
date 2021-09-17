<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Blackfire\Profile\Assertion;

use Blackfire\Profile\Assertion\AssertionsCollection;
use PHPUnit\Framework\TestCase;

class AssertionsCollectionTest extends TestCase
{
    public function testAddAssertions()
    {
        $assertionsCollection = new AssertionsCollection();
        $assertionsCollection->add('metrics.sql.queries.count >= 1', 'test');
        $assertionsCollection->add('metrics.http.requests.count == 1');

        $assertions = $assertionsCollection->getAssertions();

        $this->assertArrayHasKey('test', $assertions);
        $this->assertArrayHasKey('_assertion_1', $assertions);

        self::assertEquals(2, count($assertions));
        self::assertFalse($assertionsCollection->isEmpty());
        self::assertEquals($assertions['test'], 'metrics.sql.queries.count >= 1');
        self::assertEquals($assertions['_assertion_1'], 'metrics.http.requests.count == 1');
    }

    public function testInitialCollectionState()
    {
        $assertionsCollection = new AssertionsCollection();

        self::assertTrue($assertionsCollection->isEmpty());
    }
}
