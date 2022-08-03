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

use Blackfire\Client;
use Blackfire\ClientConfiguration;
use Blackfire\Exception\ExceptionInterface;
use Blackfire\Profile;
use Blackfire\Profile\Configuration as ProfileConfiguration;

trait TestCaseTrait
{
    /**
     * @var Client
     */
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
     *
     * @return Profile
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

            $this->assertProfile($config, $profile);
        } catch (ExceptionInterface $e) {
            $this->markTestSkipped($e->getMessage());
        }

        return $profile;
    }

    public function assertBlackfireProfileIsSuccessful(ProfileConfiguration $config)
    {
        try {
            $profile = self::$blackfire->getProfile($config->getUuid());
        } catch (ExceptionInterface $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $this->assertProfile($config, $profile);
    }

    protected function getBlackfireClientConfiguration()
    {
        return new ClientConfiguration();
    }

    private function assertProfile(ProfileConfiguration $config, Profile $profile)
    {
        if ($config->hasAssertions()) {
            $this->assertThat($profile, new TestConstraint());
        }
    }
}
