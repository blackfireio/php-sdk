<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Build;

class Scenario
{
    private $uuid;
    private $build;
    private $data;
    private $jobCount;
    private $status;
    private $steps;
    private $errors;

    public function __construct(Build $build, array $data = array())
    {
        $this->uuid = self::generateUuid();
        $this->build = $build;
        $this->data = $data + array('title' => null);
        $this->jobCount = 0;
        $this->status = 'in_progress';
        $this->errors = array();
        $this->steps = array();
    }

    public function getEnv()
    {
        return $this->build->getEnv();
    }

    public function getBuild()
    {
        return $this->build;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getUrl()
    {
        return $this->build->getUrl();
    }

    public function incJob()
    {
        @trigger_error('The method "%s" is deprecated since blackfire/php-sdk 2.3 and will be removed in 3.0.', E_USER_DEPRECATED);

        ++$this->jobCount;
    }

    public function getJobCount()
    {
        return $this->jobCount + count($this->steps);
    }

    public function addStep(array $step)
    {
        if (!isset($step['uuid'])) {
            $step['uuid'] = self::generateUuid();
        }
        $this->steps[] = $step;
    }

    /**
     * @return array[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getName()
    {
        return $this->data['title'];
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function addErrors(array $errors)
    {
        $this->errors = array_merge($this->errors, $errors);
    }

    private static function generateUuid()
    {
        if (function_exists('uuid_create')) {
            return uuid_create(\UUID_TYPE_RANDOM);
        }

        // Polyfill inspired by https://github.com/symfony/polyfill-uuid/blob/9c44518a5aff8da565c8a55dbe85d2769e6f630e/Uuid.php
        // We don't requires the polyfill for compatibility with php 5.x
        if (function_exists('random_bytes')) {
            $uuid = bin2hex(random_bytes(16));
        } else {
            $uuid = substr(sha1(uniqid()), 0, 32);
        }

        return sprintf('%08s-%04s-4%03s-%04x-%012s',
            // 32 bits for "time_low"
            substr($uuid, 0, 8),
            // 16 bits for "time_mid"
            substr($uuid, 8, 4),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            substr($uuid, 13, 3),
            // 16 bits:
            // * 8 bits for "clk_seq_hi_res",
            // * 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            hexdec(substr($uuid, 16, 4)) & 0x3FFF | 0x8000,
            // 48 bits for "node"
            substr($uuid, 20, 12)
        );
    }
}
