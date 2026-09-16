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

namespace Jblab\WideEvents\Messenger;

use Jblab\WideEvents\Core\Emission\EventEmitterInterface;
use Jblab\WideEvents\Core\Event\WideEvent;
use Jblab\WideEvents\Core\Event\WideEventContext;
use Jblab\WideEvents\Core\Normalization\WideEventLimits;
use Jblab\WideEvents\OpenTelemetry\OpenTelemetryCorrelationProvider;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

/** Creates one canonical wide event around each handled Messenger message. */
final class WideEventMiddleware implements MiddlewareInterface
{
    private ?WideEventCorrelationStamp $activeCorrelation = null;

    public function __construct(
        private readonly WideEventContext $context,
        private readonly EventEmitterInterface $emitter,
        private readonly WideEventLimits $limits,
        private readonly ?OpenTelemetryCorrelationProvider $openTelemetry = null,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->context->reset();
        $previousCorrelation     = $this->activeCorrelation;
        $correlation             = $envelope->last(WideEventCorrelationStamp::class) ?? $this->activeCorrelation;
        $correlation             ??= new WideEventCorrelationStamp(bin2hex(random_bytes(16)));
        $correlation             = $this->withOpenTelemetryCorrelation($correlation);
        $this->activeCorrelation = $correlation;

        $envelope  = $envelope->with($correlation);
        $startedAt = microtime(true);
        $outcome   = ['status' => 'success'];
        $error     = [];

        try {
            $envelope = $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            $outcome['status'] = 'failure';
            $error             = ['class' => $exception::class];

            throw $exception;
        } finally {
            $outcome['duration_ms'] = round((microtime(true) - $startedAt) * 1000, 3);

            try {
                $event = WideEvent::fromContext(
                    context: $this->context,
                    event: 'messenger.message.completed',
                    request: $correlation->toArray(),
                    message: $this->messageData($envelope),
                    outcome: $outcome,
                    error: $error,
                    limits: $this->limits,
                );
                $this->emitter->emit($event);
            } catch (\Throwable) {
                // Telemetry must never become a message handling failure.
            } finally {
                $this->context->reset();
                $this->activeCorrelation = $previousCorrelation;
            }
        }

        return $envelope;
    }

    /**
     * @return array<string, mixed>
     */
    private function messageData(Envelope $envelope): array
    {
        $message  = $envelope->getMessage();
        $data     = ['class' => $message::class];
        $received = $envelope->last(ReceivedStamp::class);
        if (null !== $received) {
            $data['transport'] = $received->getTransportName();
        }

        $handled = $envelope->last(HandledStamp::class);
        if (null !== $handled) {
            $data['handler'] = $handled->getHandlerName();
        }

        $data['retry_count'] = RedeliveryStamp::getRetryCountFromEnvelope($envelope);

        return $data;
    }

    private function withOpenTelemetryCorrelation(WideEventCorrelationStamp $correlation): WideEventCorrelationStamp
    {
        if (null === $this->openTelemetry) {
            return $correlation;
        }

        $values = $this->openTelemetry->current();

        return new WideEventCorrelationStamp(
            $correlation->requestId(),
            $values['trace_id'] ?? $correlation->traceId(),
            $values['span_id'] ?? $correlation->spanId(),
            $correlation->causationId(),
        );
    }
}
