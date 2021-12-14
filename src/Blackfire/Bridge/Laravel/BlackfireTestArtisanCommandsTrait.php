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

trait BlackfireTestArtisanCommandsTrait
{
    /**
     * Run an Artisan console command by name.
     *
     * @param string                                                 $command
     * @param \Symfony\Component\Console\Output\OutputInterface|null $outputBuffer
     *
     * @return int
     *
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function call($command, array $parameters = array(), $outputBuffer = null)
    {
        if (app()->runningUnitTests() && array_key_exists('blackfire-laravel-tests', $parameters)) {
            $result_code = null;
            exec('APP_ENV=testing blackfire run --env='.env('BLACKFIRE_TEST_ENVIRONMENT').' ./artisan '.$command, $outputBuffer, $result_code);

            return $result_code;
        }

        return parent::call($command, $parameters, $outputBuffer);
    }
}
