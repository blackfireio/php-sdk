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

interface AssertionsCollectionInterface
{
    /**
     * @return string[]
     */
    public function getAssertions();

    /**
     * @param string $assertion
     * @param string|null $name
     */
    public function add($assertion, $name = null);

    /**
     * @return bool
     */
    public function isEmpty();
}
