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

class MetricMatcher
{
    private $selector;
    private $argument;

    public function __construct($selector)
    {
        $this->selector = $selector;
    }

    public function selectArgument($indice, $matcher)
    {
        $this->argument = array($indice, $matcher);

        return $this;
    }

    /**
     * @internal
     */
    public function toYaml($indent)
    {
        $indent = str_repeat(' ', $indent);

        $yaml = sprintf("%s- callee:\n", $indent);
        $yaml .= sprintf("%s    selector: '%s'\n", $indent, $this->selector);

        if (null !== $this->argument) {
            $yaml .= sprintf("%s    argument: { %d: '%s' }\n", $indent, $this->argument[0], $this->argument[1]);
        }

        return $yaml;
    }
}
