<?php

declare(strict_types=1);

/*
 * This file is part of the Jblab Wide Events Bundle package.
 *
 * Copyright (c) 2026 Julien Bonnier <julien@jblab.io>
 * SPDX-License-Identifier: Apache-2.0
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jblab\WideEvents\EventSubscriber;

use Jblab\WideEvents\Core\Emission\EventEmitterInterface;
use Jblab\WideEvents\Core\Event\WideEvent;
use Jblab\WideEvents\Core\Event\WideEventContext;
use Jblab\WideEvents\Core\Normalization\WideEventLimits;
use Jblab\WideEvents\OpenTelemetry\OpenTelemetryCorrelationProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class HttpLifecycleSubscriber implements EventSubscriberInterface
{
    private bool $completed   = false;
    private ?float $startedAt = null;

    /** @var array<string, mixed>|null */
    private ?array $pendingError = null;

    /** @var array<string, mixed> */
    private array $requestData = [];

    public function __construct(
        private readonly WideEventContext $context,
        private readonly EventEmitterInterface $emitter,
        private readonly WideEventLimits $limits,
        /** @var array<string, mixed> */
        private readonly array $serviceMetadata = [],
        private readonly bool $propagateResponseRequestId = true,
        private readonly ?OpenTelemetryCorrelationProvider $openTelemetry = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST   => ['onRequest', 1000],
            KernelEvents::EXCEPTION => ['onException', -1000],
            KernelEvents::RESPONSE  => ['onResponse', -1000],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->context->reset();
        $this->completed    = false;
        $this->startedAt    = microtime(true);
        $this->pendingError = null;

        $request   = $event->getRequest();
        $requestId = $this->requestId($request);
        $request->attributes->set('_jblab_wide_events_request_id', $requestId);
        $this->requestData = [
            'method'     => $request->getMethod(),
            'path'       => $request->getPathInfo(),
            'request_id' => $requestId,
        ];

        $route = $request->attributes->get('_route');
        if (\is_string($route) && '' !== $route) {
            $this->requestData['route'] = $route;
        }
        foreach (['trace_id', 'span_id'] as $key) {
            $value = $request->attributes->get($key);
            if ($this->isValidIdentifier($value)) {
                $this->requestData[$key] = $value;
            }
        }
        if (null !== $this->openTelemetry) {
            $this->requestData = array_replace($this->requestData, $this->openTelemetry->current());
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->propagateResponseRequestId && isset($this->requestData['request_id'])) {
            $event->getResponse()->headers->set('X-Request-Id', (string) $this->requestData['request_id']);
        }

        if ($this->completed) {
            return;
        }

        $status = $event->getResponse()->getStatusCode();
        $this->complete(
            outcome: [
                'status'      => $status >= 400 ? 'failure' : 'success',
                'http_status' => $status,
            ],
            error: $this->pendingError ?? [],
        );
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || $this->completed) {
            return;
        }

        $this->pendingError = ['class' => $event->getThrowable()::class];
    }

    /**
     * @param array<string, mixed> $outcome
     * @param array<string, mixed> $error
     */
    private function complete(array $outcome, array $error = []): void
    {
        $this->completed        = true;
        $outcome['duration_ms'] = round((microtime(true) - ($this->startedAt ?? microtime(true))) * 1000, 3);

        try {
            $event = WideEvent::fromContext(
                context: $this->context,
                event: 'http.request.completed',
                service: $this->serviceMetadata,
                request: $this->requestData,
                outcome: $outcome,
                error: $error,
                limits: $this->limits,
            );
            $this->emitter->emit($event);
        } catch (\Throwable) {
            // Telemetry must never become an application failure.
        } finally {
            $this->context->reset();
            $this->requestData  = [];
            $this->startedAt    = null;
            $this->pendingError = null;
        }
    }

    private function requestId(Request $request): string
    {
        $header = $request->headers->get('X-Request-Id');
        if ($this->isValidIdentifier($header)) {
            return (string) $header;
        }

        return bin2hex(random_bytes(16));
    }

    private function isValidIdentifier(mixed $value): bool
    {
        return \is_string($value) && '' !== $value && 128 >= \strlen($value)
            && 1 === preg_match('/^[A-Za-z0-9._:-]+$/D', $value);
    }
}
