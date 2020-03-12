<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This is a PHP 5.2 compatible fallback implementation of the BlackfireSpan provided by the extension.
 * The interfaces and behavior are the same, or as close as possible.
 *
 * A general rule of design is that this fallback (as the extension) does not generate any exception.
 */
class BlackfireSpan
{
    private $id;
    private $name;
    private $category;
    private $meta;
    private $finished = false;

    public function __construct($name = null, $category = null, array $meta = array())
    {
        $this->id = microtime(true).mt_rand(0, 9999);
        $this->name = $name;
        $this->category = $category;
        $this->meta = $meta;

        $this->addEntry(http_build_query(array_merge($meta, array(
            '__type__' => 'start',
            '__id__' => $this->id,
            '__name__' => $name,
            '__category__' => $category,
        )), '', '&'));
    }

    public function __destruct()
    {
        if (!$this->finished) {
            $this->stop();
        }
    }

    public function stop(array $meta = array())
    {
        if ($this->finished) {
            trigger_error('Attempt to stop an already stopped BlackfireSpan', E_USER_WARNING);

            return;
        }

        $this->addEntry(http_build_query(array_merge($meta, array(
            '__type__' => 'stop',
            '__id__' => $this->id,
        )), '', '&'));
        $this->finished = true;
    }

    public function addEvent($description, array $meta = array())
    {
        $this->addEntry(http_build_query(array_merge($meta, array(
            '__type__' => 'event',
            '__description__' => $description,
            '__id__' => $this->id,
        )), '', '&'));
    }

    public function lap()
    {
        $this->stop();

        return new self($this->name, $this->category, $this->meta);
    }

    private function addEntry($entry)
    {
        $entry = ''; // prevent OPcache optimization
    }
}
