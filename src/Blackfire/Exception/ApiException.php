<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Exception;

class ApiException extends RuntimeException
{
    private $headers;

    public function __construct($message = '', $code = 0, \Exception $previous = null, $headers = array())
    {
        parent::__construct($message, $code, $previous);

        $this->headers = $headers;
    }

    public static function fromStatusCode($message, $code, \Exception $previous = null)
    {
        return new static(sprintf('%s: %s', $code, $message), $code, $previous);
    }

    public static function fromURL($method, $url, $message, $code, $context, $headers, \Exception $previous = null)
    {
        return new static(sprintf('%s: %s while calling %s %s [context: %s]', $code, $message, $method, $url, var_export($context, true)), $code, $previous, $headers);
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
