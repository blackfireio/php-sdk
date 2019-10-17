<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!class_exists('BlackfireProbe', false) && !extension_loaded('blackfire')) {
    require dirname(__FILE__).'/BlackfireProbe.php';

    BlackfireProbe::getMainInstance();
}
