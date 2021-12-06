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

use Behat\Behat\EventDispatcher\Event\ScenarioLikeTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Mink\Mink;
use Blackfire\Bridge\Behat\BlackfireExtension\ServiceContainer\Driver\BlackfireDriver;
use Blackfire\Build\BuildHelper;
use Blackfire\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ScenarioSubscriber implements EventSubscriberInterface
{
    private $buildHelper;
    private $mink;

    public function __construct(BuildHelper $buildHelper, Mink $mink)
    {
        $this->buildHelper = $buildHelper;
        $this->mink = $mink;
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            ScenarioTested::BEFORE => 'beforeScenario',
            ScenarioTested::AFTER => 'afterScenario',
        );
    }

    public function beforeScenario(ScenarioLikeTested $event)
    {
        if (!$this->buildHelper->isEnabled()) {
            return;
        }

        if (!$this->isBlackfiredScenario($event->getFeature(), $event->getScenario())) {
            return;
        }

        $this->buildHelper->createScenario($event->getScenario()->getTitle());
    }

    public function afterScenario(ScenarioLikeTested $event)
    {
        if (!$this->buildHelper->hasCurrentScenario()) {
            return;
        }

        try {
            $this->buildHelper->endCurrentScenario();
        } catch (ApiException $e) {
            $this->buildHelper->endCurrentBuild();
            throw new \RuntimeException("Blackfire: an error occurred with your scenario.\nDid you disable profiling in all its context steps?");
        }
    }

    private function isBlackfiredScenario(FeatureNode $feature, ScenarioInterface $scenario)
    {
        return $this->mink->getSession()->getDriver() instanceof BlackfireDriver;
    }
}
