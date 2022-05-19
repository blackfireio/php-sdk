<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Symfony;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;

class MonitoredMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (null === $envelope->last(ConsumedByWorkerStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        $txName = \get_class($envelope->getMessage());

        if (version_compare(phpversion('blackfire'), '1.78.0', '>=')) {
            \BlackfireProbe::startTransaction($txName);
        } else {
            \BlackfireProbe::startTransaction();
            \BlackfireProbe::setTransactionName($txName);
        }

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            \BlackfireProbe::stopTransaction();
        }
    }
}
