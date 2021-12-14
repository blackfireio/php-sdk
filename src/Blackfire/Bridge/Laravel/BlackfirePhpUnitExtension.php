<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Laravel;

use PHPUnit\Runner\AfterLastTestHook;

final class BlackfirePhpUnitExtension implements AfterLastTestHook
{
    public function executeAfterLastTest(): void
    {
        $buildHelper = BlackfireTestCase::getBuildHelper();
        if (!$buildHelper) {
            return;
        }

        if ($buildHelper->hasCurrentBuild()) {
            $build = $buildHelper->getCurrentBuild();
            echo "\n\n  Check current build:";
            echo "\n\033[01;36m  {$build->getUrl()} \033[0m\n\n";

            $buildHelper->endCurrentBuild();
        }
    }
}
