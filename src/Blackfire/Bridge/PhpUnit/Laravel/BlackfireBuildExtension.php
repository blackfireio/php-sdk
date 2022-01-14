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

use Blackfire\Bridge\PhpUnit\BlackfireBuildExtension as DefaultBlackfireBuildExtension;
use Blackfire\Build\BuildHelper;

final class BlackfireBuildExtension extends DefaultBlackfireBuildExtension
{
    public function __construct(
        string $blackfireEnvironmentId,
        string $buildTitle = 'Laravel Tests',
        ?BuildHelper $buildHelper = null
    ) {
        if (!$buildHelper) {
            $buildHelper = BuildHelper::getInstance();
        }

        parent::__construct($blackfireEnvironmentId, $buildTitle, $buildHelper);
    }
}
