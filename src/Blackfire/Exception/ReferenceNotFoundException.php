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

@trigger_error('The \Blackfire\Exception\ReferenceNotFoundException class is deprecated since blackfire/php-sdk 1.18 and will be removed in 2.0.', E_USER_DEPRECATED);

/**
 * @deprecated since 1.18, to be removed in 2.0.
 */
class ReferenceNotFoundException extends RuntimeException
{
}
