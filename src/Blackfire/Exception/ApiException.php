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

class ApiException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf('%s: %s', $code, $message), $code, $previous);
    }
}
