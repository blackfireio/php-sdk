<?php

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
