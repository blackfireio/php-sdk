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

class BlackfireTestCase extends PantherTestCase
{
    use BlackfireTestCaseTrait;
}
