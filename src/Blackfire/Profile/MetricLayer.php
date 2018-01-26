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

class MetricLayer extends Metric
{
    public function __construct($name, $label = null)
    {
        parent::__construct($name);
        $this->setLayer($name);
        $this->setLabel($label ?: $name);
    }

    public function addCallee($selector)
    {
        throw new \LogicException('A layer cannot have callees.');
    }
}
