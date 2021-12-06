<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Behat\BlackfireExtension\Event;

use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Behat\Testwork\Output\Formatter;
use Blackfire\Build\BuildHelper;
use Blackfire\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BuildSubscriber implements EventSubscriberInterface
{
    private $printer;
    private $buildHelper;
    private $blackfireEnvironmentId;
    private $buildTitle;
    private $externalId;
    private $externalParentId;

    public function __construct(Formatter $formatter, BuildHelper $buildHelper, string $blackfireEnvironmentId, string $buildTitle = 'Build from Behat')
    {
        $this->printer = $formatter->getOutputPrinter();
        $this->blackfireEnvironmentId = $blackfireEnvironmentId;
        $this->buildTitle = $buildTitle;
        $this->externalId = $this->getEnv('BLACKFIRE_EXTERNAL_ID');
        $this->externalParentId = $this->getEnv('BLACKFIRE_EXTERNAL_PARENT_ID');
        $this->buildHelper = $buildHelper;
        $this->buildHelper->setEnabled($this->isGloballyEnabled());
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

    public static function getSubscribedEvents(): array
    {
        return array(
            SuiteTested::BEFORE => 'prepareBuild',
            SuiteTested::BEFORE_TEARDOWN => 'endUpBuild',
        );
    }

    public function prepareBuild(SuiteTested $event)
    {
        if (!$this->buildHelper->isEnabled()) {
            return;
        }

        $this->buildHelper->deferBuild(
            $this->blackfireEnvironmentId,
            sprintf('%s (%s)', $this->buildTitle, $event->getSuite()->getName()),
            $this->externalId,
            $this->externalParentId,
            'Behat'
        );
    }

    public function endUpBuild(SuiteTested $event)
    {
        if (!$this->buildHelper->hasCurrentBuild()) {
            return;
        }

        $build = $this->buildHelper->getCurrentBuild();
        if (0 === $build->getScenarioCount()) {
            $this->printer->writeln($this->formatMessage('Blackfire: No scenario was created for the current build.', 'skipped'));
            $this->buildHelper->endCurrentBuild();

            return;
        }

        if ($this->buildHelper->hasCurrentScenario()) {
            $this->printer->writeln($this->formatMessage('The last scenario was not ended.', 'pending_param', true));

            $this->buildHelper->endCurrentScenario();
        }

        $report = $this->buildHelper->endCurrentBuild();

        try {
            $this->printer->writeln($this->formatMessage('Blackfire Report:', 'keyword', true));
            $style = 'failed';
            if ($report->isErrored()) {
                $this->printer->writeln($this->formatMessage('The build has errored', $style));
            } else {
                if ($report->isSuccessful()) {
                    $style = 'passed';
                    $this->printer->writeln($this->formatMessage('The build was successful', $style));
                } else {
                    $this->printer->writeln($this->formatMessage('The build has failed', $style));
                }
            }

            $this->printer->writeln($this->formatMessage('Full report is available here:', $style));
            $this->printer->writeln($this->formatMessage($report->getUrl(), 'info'));
        } catch (ApiException $e) {
            $this->printer->writeln($this->formatMessage('An error has occured while communicating with Blackfire.io', 'error'));
            $this->printer->writeln($this->formatMessage($e->getMessage(), 'exception'));
        } finally {
            $this->printer->writeln();
        }
    }

    private function formatMessage(string $message, string $style, bool $isHeader = false): string
    {
        return sprintf(
            '%s{+%s}%s{-%s}%s',
            $isHeader ? '' : '    ',
            $style,
            $message,
            $style,
            $isHeader ? PHP_EOL : ''
        );
    }
}
