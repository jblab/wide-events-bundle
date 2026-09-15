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

namespace Jblab\WideEvents\Tests\Integration\Http;

use Jblab\WideEvents\Core\Emission\InMemoryEventEmitter;
use Jblab\WideEvents\Core\Event\WideEventContext;
use Jblab\WideEvents\Core\Normalization\WideEventLimits;
use Jblab\WideEvents\EventSubscriber\HttpLifecycleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class HttpLifecycleSubscriberTest extends TestCase
{
    public function testItEmitsOneEventForTheMainRequest(): void
    {
        $destination = new InMemoryEventEmitter();
        $subscriber  = $this->subscriber($destination);
        $request     = Request::create('/orders/42', 'GET', server: ['HTTP_X_REQUEST_ID' => 'req-42']);
        $request->attributes->set('_route', 'order_show');
        $kernel = $this->createStub(HttpKernelInterface::class);

        $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $response = new Response(status: 201);
        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertCount(1, $destination->events());
        self::assertSame([
            'method'     => 'GET',
            'path'       => '/orders/42',
            'request_id' => 'req-42',
            'route'      => 'order_show',
        ], $destination->events()[0]->toArray()['request']);
        self::assertSame(201, $destination->events()[0]->toArray()['outcome']['http_status']);
        self::assertSame('req-42', $response->headers->get('X-Request-Id'));
    }

    public function testResponseRequestIdPropagationCanBeDisabled(): void
    {
        $destination = new InMemoryEventEmitter();
        $subscriber  = $this->subscriber($destination, false);
        $request     = Request::create('/orders/42', 'GET', server: ['HTTP_X_REQUEST_ID' => 'req-42']);
        $kernel      = $this->createStub(HttpKernelInterface::class);
        $response    = new Response();

        $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertCount(1, $destination->events());
        self::assertFalse($response->headers->has('X-Request-Id'));
    }

    public function testSubrequestsDoNotStartOrCompleteAnEvent(): void
    {
        $destination = new InMemoryEventEmitter();
        $subscriber  = $this->subscriber($destination);
        $request     = Request::create('/fragment');
        $kernel      = $this->createStub(HttpKernelInterface::class);

        $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));
        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, new Response()));

        self::assertSame([], $destination->events());
    }

    public function testExceptionIsRecordedAndEmittedWithTheFinalResponseStatus(): void
    {
        $destination = new InMemoryEventEmitter();
        $subscriber  = $this->subscriber($destination);
        $request     = Request::create('/broken');
        $kernel      = $this->createStub(HttpKernelInterface::class);

        $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $subscriber->onException(
            new \Symfony\Component\HttpKernel\Event\ExceptionEvent(
                $kernel,
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                new \RuntimeException('not emitted'),
            )
        );

        $response = new Response(status: 404);
        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        $events = $destination->events();
        self::assertCount(1, $events);
        self::assertSame(\RuntimeException::class, $events[0]->toArray()['error']['class']);
        self::assertSame(404, $events[0]->toArray()['outcome']['http_status']);
    }

    public function testExceptionDoesNotEmitBeforeAResponseExists(): void
    {
        $destination = new InMemoryEventEmitter();
        $subscriber  = $this->subscriber($destination);
        $request     = Request::create('/broken');
        $kernel      = $this->createStub(HttpKernelInterface::class);

        $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $subscriber->onException(
            new \Symfony\Component\HttpKernel\Event\ExceptionEvent(
                $kernel,
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                new \RuntimeException('not emitted'),
            )
        );

        self::assertSame(0, \count($destination->events()));
    }

    private function subscriber(
        InMemoryEventEmitter $emitter,
        bool $propagateResponseRequestId = true,
    ): HttpLifecycleSubscriber {
        return new HttpLifecycleSubscriber(
            new WideEventContext(),
            $emitter,
            new WideEventLimits(),
            ['name' => 'test-service'],
            $propagateResponseRequestId,
        );
    }
}
