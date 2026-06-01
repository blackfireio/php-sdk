<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\PhpUnit;

use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\WebTestAssertionsTrait;

class BlackfireTestCase extends PantherTestCase
{
    use WebTestAssertionsTrait;

    public function tearDown(): void
    {
        // Enforce to "quit" the browser session.
        self::$httpBrowserClient = null;
        parent::tearDown();
    }
}
