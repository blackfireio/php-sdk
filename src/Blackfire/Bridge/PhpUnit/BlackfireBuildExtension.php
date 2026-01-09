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

if (class_exists('PHPUnit\Runner\Version') && version_compare(\PHPUnit\Runner\Version::id(), '10.0.0', '>=')) {
    class_alias('Blackfire\Bridge\PhpUnit\BlackfireBuildExtension10', 'Blackfire\Bridge\PhpUnit\BlackfireBuildExtension');
} else {
    class_alias('Blackfire\Bridge\PhpUnit\BlackfireBuildExtension9', 'Blackfire\Bridge\PhpUnit\BlackfireBuildExtension');
}

if (false) {
    class BlackfireBuildExtension
    {
    }
}
