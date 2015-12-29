<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\PhpUnit;

use Blackfire\Client;
use Blackfire\Profile\Configuration as ProfileConfiguration;
use Blackfire\ClientConfiguration;
use Blackfire\Exception\ExceptionInterface;

trait TestCaseTrait
{
    private static $blackfire;

    /**
     * @before
     */
    protected function createBlackfire()
    {
        if (!self::$blackfire) {
            self::$blackfire = new Client($this->getBlackfireClientConfiguration());
        }
    }

    /**
     * @param callable $callback The code to profile
     */
    public function assertBlackfire(ProfileConfiguration $config, $callback)
    {
        if (!$config->hasMetadata('skip_timeline')) {
            $config->setMetadata('skip_timeline', 'true');
        }

        try {
            $probe = self::$blackfire->createProbe($config);

            $callback();

            $profile = self::$blackfire->endProbe($probe);

            $this->assertThat($profile, new TestConstraint());
        } catch (ExceptionInterface $e) {
            $this->markTestSkipped($e->getMessage());
        }

        return $profile;
    }

    protected function getBlackfireClientConfiguration()
    {
        return new ClientConfiguration();
    }
}
