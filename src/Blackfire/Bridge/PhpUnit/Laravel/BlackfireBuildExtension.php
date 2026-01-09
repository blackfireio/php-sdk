<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\PhpUnit\Laravel;

use Blackfire\Build\BuildHelper;

if (class_exists('PHPUnit\Runner\Version') && version_compare(\PHPUnit\Runner\Version::id(), '10.0.0', '>=')) {
    class BlackfireBuildExtension extends \Blackfire\Bridge\PhpUnit\BlackfireBuildExtension10
    {
    }
} else {
    class BlackfireBuildExtension extends \Blackfire\Bridge\PhpUnit\BlackfireBuildExtension9
    {
        public function __construct(
            string $blackfireEnvironmentId,
            string $buildTitle = 'Laravel Tests',
            ?BuildHelper $buildHelper = null,
        ) {
            if (!$buildHelper) {
                $buildHelper = BuildHelper::getInstance();
            }

            parent::__construct($blackfireEnvironmentId, $buildTitle, $buildHelper);
        }
    }
}
