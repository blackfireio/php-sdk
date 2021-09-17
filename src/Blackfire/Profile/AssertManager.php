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

class AssertManager
{
    /**
     * @var array<string, string>
     */
    private $assertions = [];

    /**
     * @param string $assertion
     * @param string|null $name
     *
     * @return self
     */
    public function assert($assertion, $name = null)
    {
        static $counter = 0;

        if (!$name) {
            $name = '_assertion_'.(++$counter);
        }

        $key = $name;
        $i = 0;
        while (isset($this->assertions[$key])) {
            $key = $name.' ('.(++$i).')';
        }

        $this->assertions[$key] = $assertion;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAssertions()
    {
        return $this->assertions;
    }
}
