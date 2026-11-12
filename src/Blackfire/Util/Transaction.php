<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Util;

/**
 * @internal
 */
final class Transaction
{
    private static $supportsExitCode;

    /**
     * Stops the current transaction. A non-zero exit code reports the transaction as failed.
     *
     * @param int|null $exitCode Like a process exit code, only its low byte is kept (300 becomes 44)
     */
    public static function stop($exitCode = null)
    {
        if (null === $exitCode || !self::supportsExitCode()) {
            \BlackfireProbe::stopTransaction();

            return;
        }

        \BlackfireProbe::stopTransaction((int) $exitCode);
    }

    private static function supportsExitCode()
    {
        if (null === self::$supportsExitCode) {
            $method = new \ReflectionMethod('BlackfireProbe', 'stopTransaction');
            self::$supportsExitCode = $method->getNumberOfParameters() > 0;
        }

        return self::$supportsExitCode;
    }
}
