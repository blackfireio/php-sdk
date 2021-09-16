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

use Blackfire\Profile\AssertManager;
use PHPUnit\Framework\TestCase;

class AssertManagerTest extends TestCase
{
    public function testAddAssertions()
    {
        $assertManager = new AssertManager();
        $assertManager->assert('metrics.sql.queries.count >= 1', 'test');
        $assertManager->assert('metrics.http.requests.count == 1');

        $assertions = $assertManager->getAssertions();

        $this->assertArrayHasKey('test', $assertions);
        $this->assertArrayHasKey('_assertion_1', $assertions);

        self::assertEquals($assertions['test'], 'metrics.sql.queries.count >= 1');
        self::assertEquals($assertions['_assertion_1'], 'metrics.http.requests.count == 1');
    }
}
