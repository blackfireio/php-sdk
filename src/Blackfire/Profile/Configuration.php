<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Profile;

use Blackfire\Build;

/**
 * Configures a Blackfire profile.
 */
class Configuration
{
    private $uuid;
    private $assertions;
    private $metrics;
    private $samples = 1;
    private $reference;
    private $title = '';
    private $isReference = false;
    private $metadata = array();
    private $layers = array();
    private $scenario;
    private $requestInfo = array();
    private $intention;
    private $debug = false;

    /**
     * @deprecated since 1.14, to be removed in 2.0.
     */
    private $build;

    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Sets the UUID of the profile to an existing one.
     *
     * @param string $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

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

    /**
     * @return Build
     *
     * @deprecated since 1.14, to be removed in 2.0. Use method "getScenario" instead.
     */
    public function getBuild()
    {
        // BC: do not trigger the deprecation notice if the build has not been set,
        // because the method is always called in \Blackfire\Client.
        if (!$this->build) {
            return;
        }

        @trigger_error('The method "getBuild" is deprecated since blackfire/php-sdk 1.14 and will be removed in 2.0. Use method "getScenario" instead.', E_USER_DEPRECATED);

        return $this->build;
    }

    /**
     * @return $this
     *
     * @deprecated since 1.14, to be removed in 2.0. Use method "setScenario" instead.
     */
    public function setBuild(Build $build)
    {
        @trigger_error('The method "setBuild" is deprecated since blackfire/php-sdk 1.14 and will be removed in 2.0. Use method "setScenario" instead.', E_USER_DEPRECATED);

        $this->build = $build;

        return $this;
    }

    public function getScenario()
    {
        return $this->scenario;
    }

    /**
     * @return $this
     */
    public function setScenario(Build\Scenario $scenario)
    {
        $this->scenario = $scenario;

        return $this;
    }

    public function getRequestInfo()
    {
        return $this->requestInfo;
    }

    /**
     * @return $this
     */
    public function setRequestInfo(array $info)
    {
        $this->requestInfo = $info;

        return $this;
    }

    /**
     * @deprecated since 1.18, to be removed in 2.0.
     */
    public function getReference()
    {
        @trigger_error('The method "getReference" is deprecated since blackfire/php-sdk 1.18 and will be removed in 2.0.', E_USER_DEPRECATED);

        return $this->reference;
    }

    /**
     * @internal
     */
    public function getReferenceInternal()
    {
        return $this->reference;
    }

    /**
     * @return $this
     *
     * @deprecated since 1.18, to be removed in 2.0.
     */
    public function setReference($reference)
    {
        @trigger_error('The method "setReference" is deprecated since blackfire/php-sdk 1.18 and will be removed in 2.0.', E_USER_DEPRECATED);

        $this->reference = $reference;

        return $this;
    }

    /**
     * @deprecated since 1.18, to be removed in 2.0.
     */
    public function isNewReference()
    {
        @trigger_error('The method "isNewReference" is deprecated since blackfire/php-sdk 1.18 and will be removed in 2.0.', E_USER_DEPRECATED);

        return $this->isReference;
    }

    /**
     * @internal
     */
    public function isNewReferenceInternal()
    {
        return $this->isReference;
    }

    /**
     * @return $this
     *
     * @deprecated since 1.18, to be removed in 2.0.
     */
    public function setAsReference()
    {
        @trigger_error('The method "setAsReference" is deprecated since blackfire/php-sdk 1.18 and will be removed in 2.0.', E_USER_DEPRECATED);

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
     * @return bool
     */
    public function hasAssertions()
    {
        return (bool) $this->assertions;
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

    public function getIntention()
    {
        return $this->intention;
    }

    /**
     * @return $this
     */
    public function setIntention($intention)
    {
        $this->intention = (string) $intention;

        return $this;
    }

    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->debug = (bool) $debug;

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
                $yaml .= "    assertions: [\"$assertion\"]\n\n";
            }
        }

        return $yaml;
    }
}
