<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Profile;

/**
 * Configures a Blackfire profile.
 */
class Configuration
{
    private $assertions;
    private $metrics;
    private $samples = 1;
    private $reference;
    private $title;
    private $isReference = false;
    private $metadata = array();
    private $layers = array();

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return $this
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    public function isNewReference()
    {
        return $this->isReference;
    }

    /**
     * @return $this
     */
    public function setAsReference()
    {
        $this->isReference = true;

        return $this;
    }

    public function hasMetadata($key)
    {
        return array_key_exists($key, $this->metadata);
    }

    public function getMetadata($key)
    {
        if (!array_key_exists($key, $this->metadata)) {
            throw new \LogicException(sprintf('Metadata "%s" is not set.', $key));
        }

        return $this->metadata[$key];
    }

    public function getAllMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return $this
     */
    public function setMetadata($key, $value)
    {
        if (!is_string($value)) {
            throw new \LogicException(sprintf('Metadata values must be strings ("%s" given).', is_object($value) ? get_class($value) : gettype($value)));
        }

        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function assert($assertion, $name = '')
    {
        static $counter = 0;

        if (!$name) {
            $name = '_assertion_'.(++$counter);
        }

        $key = $name;
        $i = 0;
        while (isset($this->assertions[$key])) {
            $key = $name.' ('.(++$i).')';
        }

        $this->assertions[$key] = $assertion;

        return $this;
    }

    /**
     * @return $this
     */
    public function defineLayer(MetricLayer $layer)
    {
        $this->layers[] = $layer;

        return $this;
    }

    /**
     * @return $this
     */
    public function defineMetric(Metric $metric)
    {
        $this->metrics[] = $metric;

        return $this;
    }

    public function getSamples()
    {
        return $this->samples;
    }

    /**
     * @return $this
     */
    public function setSamples($samples)
    {
        $this->samples = (int) $samples;

        return $this;
    }

    /**
     * @internal
     */
    public function toYaml()
    {
        if (!$this->assertions && !$this->metrics) {
            return;
        }

        $yaml = '';

        if ($this->metrics) {
            $yaml .= "metrics:\n";
            foreach ($this->layers as $layer) {
                $yaml .= $layer->toYaml();
            }

            foreach ($this->metrics as $metric) {
                $yaml .= $metric->toYaml();
            }
        }

        if ($this->assertions) {
            $yaml .= "tests:\n";
            foreach ($this->assertions as $name => $assertion) {
                $yaml .= "  \"$name\":\n";
                $yaml .= "    command: .*\n";
                $yaml .= "    assertions: [\"$assertion\"]\n\n";
            }
        }

        return $yaml;
    }
}
