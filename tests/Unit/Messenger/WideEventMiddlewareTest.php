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

namespace Jblab\WideEvents\Tests\Unit\Messenger;

use Jblab\WideEvents\Core\Emission\InMemoryEventEmitter;
use Jblab\WideEvents\Core\Event\WideEventContext;
use Jblab\WideEvents\Core\Normalization\WideEventLimits;
use Jblab\WideEvents\Messenger\WideEventCorrelationStamp;
use Jblab\WideEvents\Messenger\WideEventMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

final class WideEventMiddlewareTest extends TestCase
{
    public function testItEmitsSuccessWithCorrelationAndMessageMetadata(): void
    {
        $emitter    = new InMemoryEventEmitter();
        $context    = new WideEventContext();
        $middleware = new WideEventMiddleware($context, $emitter, new WideEventLimits());
        $envelope   = (new Envelope(new TestMessage()))
            ->with(new WideEventCorrelationStamp('request-1', 'trace-1', 'span-1', 'cause-1'))
            ->with(new ReceivedStamp('async'))
            ->with(new RedeliveryStamp(2));

        $middleware->handle($envelope, new HandledMiddlewareStack());

        self::assertCount(1, $emitter->events());
        $payload = $emitter->events()[0]->toArray();
        self::assertSame([
            'request_id'   => 'request-1',
            'trace_id'     => 'trace-1',
            'span_id'      => 'span-1',
            'causation_id' => 'cause-1',
        ], $payload['request']);
        self::assertSame([
            'class'       => TestMessage::class,
            'transport'   => 'async',
            'handler'     => 'TestHandler::__invoke',
            'retry_count' => 2,
        ], $payload['message']);
        self::assertSame('success', $payload['outcome']['status']);
        self::assertSame([], $payload['error']);
        self::assertSame([], $context->all());
    }

    public function testItEmitsSafeFailureDetailsAndRethrows(): void
    {
        $emitter    = new InMemoryEventEmitter();
        $middleware = new WideEventMiddleware(new WideEventContext(), $emitter, new WideEventLimits());
        $exception  = new \RuntimeException('not emitted');

        try {
            $middleware->handle(new Envelope(new TestMessage()), new ThrowingMiddlewareStack($exception));
            self::fail('The handler exception should be rethrown.');
        } catch (\RuntimeException $caught) {
            self::assertSame($exception, $caught);
        }

        $payload = $emitter->events()[0]->toArray();
        self::assertSame('failure', $payload['outcome']['status']);
        self::assertSame(['class' => \RuntimeException::class], $payload['error']);
        self::assertArrayNotHasKey('message', $payload['error']);
    }

    public function testItDoesNotLeakCorrelationBetweenSequentialMessages(): void
    {
        $emitter    = new InMemoryEventEmitter();
        $middleware = new WideEventMiddleware(new WideEventContext(), $emitter, new WideEventLimits());

        $middleware->handle(
            (new Envelope(new TestMessage()))->with(new WideEventCorrelationStamp('request-1')),
            new SingleMiddlewareStack(),
        );
        $middleware->handle(new Envelope(new TestMessage()), new SingleMiddlewareStack());

        self::assertCount(2, $emitter->events());
        $firstRequestId  = $emitter->events()[0]->toArray()['request']['request_id'];
        $secondRequestId = $emitter->events()[1]->toArray()['request']['request_id'];
        self::assertSame('request-1', $firstRequestId);
        self::assertNotSame($firstRequestId, $secondRequestId);
    }

    public function testNestedHandlingInheritsTheActiveCorrelation(): void
    {
        $emitter    = new InMemoryEventEmitter();
        $middleware = new WideEventMiddleware(new WideEventContext(), $emitter, new WideEventLimits());

        $middleware->handle(
            (new Envelope(new TestMessage()))->with(new WideEventCorrelationStamp('request-1', 'trace-1')),
            new NestedMiddlewareStack($middleware),
        );

        self::assertCount(2, $emitter->events());
        self::assertSame(
            ['request_id' => 'request-1', 'trace_id' => 'trace-1'],
            $emitter->events()[1]->toArray()['request'],
        );
    }
}

final class TestMessage
{
}

final class SingleMiddlewareStack implements StackInterface
{
    public function next(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                return $envelope;
            }
        };
    }
}

final class HandledMiddlewareStack implements StackInterface
{
    public function next(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                return $envelope->with(new HandledStamp(null, 'TestHandler::__invoke'));
            }
        };
    }
}

final class NestedMiddlewareStack implements StackInterface
{
    public function __construct(private readonly WideEventMiddleware $middleware)
    {
    }

    public function next(): MiddlewareInterface
    {
        $middleware = $this->middleware;

        return new class($middleware) implements MiddlewareInterface {
            public function __construct(private readonly WideEventMiddleware $middleware)
            {
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                $this->middleware->handle(new Envelope(new NestedTestMessage()), new SingleMiddlewareStack());

                return $envelope;
            }
        };
    }
}

final class NestedTestMessage
{
}

final class ThrowingMiddlewareStack implements StackInterface
{
    public function __construct(private readonly \Throwable $exception)
    {
    }

    public function next(): MiddlewareInterface
    {
        $exception = $this->exception;

        return new class($exception) implements MiddlewareInterface {
            public function __construct(private readonly \Throwable $exception)
            {
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                throw $this->exception;
            }
        };
    }
}
