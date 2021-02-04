<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Behat\Context;

use Behat\Behat\Hook\Scope\StepScope;
use Behat\Mink\Driver\DriverInterface;
use Blackfire\Bridge\Behat\BlackfireExtension\ServiceContainer\Driver\BlackfireDriver;
use Blackfire\Bridge\Symfony\BlackfiredHttpBrowser;

trait BlackfireContextTrait
{
    protected function disableProfiling()
    {
        if (!$this->isBlackfireDriver()) {
            return;
        }

        $this->getCurrentDriver()->getClient()->disableProfiling();
    }

    protected function enableProfiling()
    {
        if (!$this->isBlackfireDriver()) {
            return;
        }

        $this->getCurrentDriver()->getClient()->enableProfiling();
    }

    private function getCurrentDriver(): DriverInterface
    {
        return $this->getSession()->getDriver();
    }

    private function isBlackfireDriver()
    {
        return $this->getCurrentDriver() instanceof BlackfireDriver;
    }

    /**
     * @AfterStep
     */
    public function afterStep(StepScope $scope)
    {
        if (!$this->isBlackfireDriver()) {
            return;
        }

        /** @var BlackfiredHttpBrowser $client */
        $client = $this->getCurrentDriver()->getClient();
        if (!$client->isProfilingEnabled()) {
            $client->enableProfiling();
        }
    }
}
