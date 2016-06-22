<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Exception;

class ApiException extends RuntimeException
{
    public static function fromStatusCode($message, $code, \Exception $previous = null)
    {
        return new static(sprintf('%s: %s', $code, $message), $code, $previous);
    }

    public static function fromURL($method, $url, $message, $code, $context, $headers, \Exception $previous = null)
    {
        return new static(sprintf('%s: %s while calling %s %s [context: %s] [headers: %s]', $code, $message, $method, $url, var_export($context, true), var_export($headers, true)), $code, $previous);
    }
}
