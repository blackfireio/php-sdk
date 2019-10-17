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

class Metric
{
    private $name;
    private $layer;
    private $label;
    private $matchers = array();

    public function __construct($name, $selectors = null)
    {
        $this->name = $this->label = $name;

        foreach ((array) $selectors as $selector) {
            $this->addCallee($selector);
        }
    }

    /**
     * @return $this
     */
    public function setLayer($layer)
    {
        $this->layer = $layer;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return MetricMatcher
     */
    public function addCallee($selector)
    {
        $this->matchers[] = $matcher = new MetricMatcher($selector);

        return $matcher;
    }

    /**
     * @internal
     */
    public function toYaml()
    {
        $yaml = "  \"$this->name\":\n";
        $yaml .= "    label: \"$this->label\"\n";
        if (null !== $this->layer) {
            $yaml .= "    layer: \"$this->layer\"\n";
        }

        if ($this->matchers) {
            $yaml .= "    matching_calls:\n";
            $yaml .= "      php:\n";

            foreach ($this->matchers as $matcher) {
                $yaml .= $matcher->toYaml(8);
            }
        }

        return $yaml;
    }
}
