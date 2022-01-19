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

use Blackfire\Build\BuildHelper;
use Symfony\Component\Process\Process;

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
        if ('testing' === env('APP_ENV') && array_key_exists('blackfire-laravel-tests', $parameters)) {
            $buildHelper = BuildHelper::getInstance();

            $process = new Process(
                array_merge(array(
                    'blackfire',
                    'run',
                    '--env='.$buildHelper->getBlackfireEnvironmentId(),
                    './artisan',
                 ), explode(' ', $command)),
                null,
                array(
                    'APP_ENV' => 'testing',
                )
            );

            $process->run();
            echo $process->getErrorOutput();

            unset($parameters['blackfire-laravel-tests']);
        }

        return parent::call($command, $parameters, $outputBuffer);
    }
}
