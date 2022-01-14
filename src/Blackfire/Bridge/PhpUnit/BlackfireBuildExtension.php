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

use Blackfire\Build\BuildHelper;
use Blackfire\Exception\ApiException;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Util\Color;

class BlackfireBuildExtension implements BeforeFirstTestHook, AfterLastTestHook, AfterTestHook
{
    /** @var BuildHelper */
    private $buildHelper;

    /** @var string */
    private $blackfireEnvironmentId;

    /** @var string */
    private $buildTitle;

    /** @var ?string */
    private $externalId;

    /** @var ?string */
    private $externalParentId;

    public function __construct(
        string $blackfireEnvironmentId,
        string $buildTitle = 'Build from PHPUnit',
        ?BuildHelper $buildHelper = null
    ) {
        $this->blackfireEnvironmentId = $blackfireEnvironmentId;
        $this->buildTitle = $buildTitle;
        $this->externalId = $this->getEnv('BLACKFIRE_EXTERNAL_ID');
        $this->externalParentId = $this->getEnv('BLACKFIRE_EXTERNAL_PARENT_ID');
        $this->buildHelper = $buildHelper ?? BuildHelper::getInstance();
        $this->buildHelper->setEnabled($this->isGloballyEnabled());
        $this->buildHelper->setBlackfireEnvironmentId($blackfireEnvironmentId);
    }

    private function getEnv(string $envVarName): ?string
    {
        $value = getenv($envVarName);

        return false === $value ? null : $value;
    }

    private function isGloballyEnabled(): bool
    {
        $buildDisabled = $this->getEnv('BLACKFIRE_BUILD_DISABLED');

        return \is_null($buildDisabled) || '0' === $buildDisabled;
    }

    public function executeBeforeFirstTest(): void
    {
        if (!$this->buildHelper->isEnabled()) {
            return;
        }

        $this->buildHelper->deferBuild($this->blackfireEnvironmentId, $this->buildTitle, $this->externalId, $this->externalParentId, 'PHPUnit');
    }

    public function executeAfterLastTest(): void
    {
        if (!$this->buildHelper->hasCurrentBuild()) {
            return;
        }

        $build = $this->buildHelper->getCurrentBuild();
        if (0 === $build->getScenarioCount()) {
            echo "\n";
            echo Color::colorize('bg-yellow', 'Blackfire: No scenario was created for the current build.');
            echo "\n";

            $this->buildHelper->endCurrentBuild();

            return;
        }

        if ($this->buildHelper->hasAnyScenario()) {
            echo "\n";
            echo Color::colorize('bg-yellow', 'Blackfire: The last scenario was not ended.');
            echo "\n";

            $this->buildHelper->endAllScenarios();
        }

        $report = $this->buildHelper->endCurrentBuild();

        try {
            if ($report->isErrored()) {
                echo "\n";
                echo Color::colorize('bg-red,fg-white', 'Blackfire: The build has errored');
                echo "\n";
            } else {
                if ($report->isSuccessful()) {
                    echo "\n";
                    echo Color::colorize('bg-green', 'Blackfire: The build was successful');
                    echo "\n";
                } else {
                    echo "\n";
                    echo Color::colorize('bg-red,fg-white', 'Blackfire: The build has failed');
                    echo "\n";
                }
            }

            echo "Full report is available here:\n";
            echo "{$report->getUrl()}\n";
        } catch (ApiException $e) {
            echo "\n";
            echo Color::colorize('bg-red,fg-white', 'Blackfire: The build has errored');
            echo "\n";
        }
    }

    public function executeAfterTest(string $test, float $time): void
    {
        list($class) = explode('::', $test);
        if (!method_exists($class, 'isBlackfireScenarioAutoStart')) {
            return;
        }

        if (true === call_user_func("$class::isBlackfireScenarioAutoStart")) {
            return;
        }

        // If scenario is not automatically started, it should at least be ended
        // at the end of each test.
        // This is to avoid an exception when creating a new scenario.
        if ($this->buildHelper->hasCurrentScenario()) {
            $this->buildHelper->endCurrentScenario();
        }
    }
}
