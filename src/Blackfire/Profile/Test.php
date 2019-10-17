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
 * Represents a Blackfire Profile Test.
 *
 * Instances of this class should never be created directly.
 */
class Test
{
    private $name;
    private $state;
    private $failures;

    /**
     * @internal
     */
    public function __construct($name, $state, array $failures)
    {
        $this->name = $name;
        $this->state = $state;
        $this->failures = $failures;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return 'successful' === $this->state;
    }

    /**
     * @return bool
     */
    public function isErrored()
    {
        return 'errored' === $this->state;
    }

    /**
     * @return array
     */
    public function getFailures()
    {
        return $this->failures;
    }
}
