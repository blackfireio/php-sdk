<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Symfony\EventSubscriber;

use Blackfire\Bridge\Symfony\FrankenPHPProfiler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FrankenPHPProfilerSubscriber implements EventSubscriberInterface
{
    /**
     * @var FrankenPHPProfiler
     */
    private $profiler;

    public function __construct()
    {
        $this->profiler = new FrankenPHPProfiler();
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', 1024),
            KernelEvents::RESPONSE => array('onKernelResponse', -1024),
            KernelEvents::TERMINATE => array('onKernelTerminate', -1024),
            KernelEvents::EXCEPTION => array('onKernelException', -1024),
        );
    }

    /**
     * @return void
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!method_exists(\BlackfireProbe::class, 'setAttribute')) {
            return;
        }

        try {
            $this->profiler->start($event->getRequest());
        } catch (\UnexpectedValueException $e) {
            $this->profiler->reset();
        }
    }

    /**
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!class_exists(\BlackfireProbe::class, false)) {
            return;
        }

        try {
            $response = $event->getResponse();

            if (method_exists(\BlackfireProbe::class, 'setAttribute')) {
                \BlackfireProbe::setAttribute('http.status_code', $response->getStatusCode());
            }

            $this->profiler->stop($event->getRequest(), $response);
        } catch (\Throwable $e) {
            $this->profiler->reset();
        }
    }

    /**
     * @return void
     */
    public function onKernelTerminate(TerminateEvent $event)
    {
        $this->profiler->reset();
    }

    /**
     * @return void
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $this->profiler->reset();
    }
}
