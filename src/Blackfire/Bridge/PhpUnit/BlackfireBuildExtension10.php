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
use PHPUnit\Event\Test\Finished as TestFinished;
use PHPUnit\Event\Test\FinishedSubscriber as TestFinishedSubscriber;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\TestRunner\ExecutionStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class BlackfireBuildExtension10 implements Extension
{
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $blackfireEnvironmentId = $parameters->get('blackfireEnvironmentId');
        $buildTitle = $parameters->has('buildTitle')
            ? $parameters->get('buildTitle')
            : 'Build from PHPUnit';

        $subscriber = new BlackfireBuildSubscriber(
            $blackfireEnvironmentId,
            $buildTitle,
            BuildHelper::getInstance(),
        );

        $facade->registerSubscribers(
            new class($subscriber) implements ExecutionStartedSubscriber {
                private BlackfireBuildSubscriber $subscriber;

                public function __construct(BlackfireBuildSubscriber $subscriber)
                {
                    $this->subscriber = $subscriber;
                }

                public function notify(ExecutionStarted $event): void
                {
                    $this->subscriber->executeBeforeFirstTest();
                }
            },
            new class($subscriber) implements ExecutionFinishedSubscriber {
                private BlackfireBuildSubscriber $subscriber;

                public function __construct(BlackfireBuildSubscriber $subscriber)
                {
                    $this->subscriber = $subscriber;
                }

                public function notify(ExecutionFinished $event): void
                {
                    $this->subscriber->executeAfterLastTest();
                }
            },
            new class($subscriber) implements TestFinishedSubscriber {
                private BlackfireBuildSubscriber $subscriber;

                public function __construct(BlackfireBuildSubscriber $subscriber)
                {
                    $this->subscriber = $subscriber;
                }

                public function notify(TestFinished $event): void
                {
                    $this->subscriber->executeAfterTest($event->test()->className());
                }
            },
        );
    }
}
