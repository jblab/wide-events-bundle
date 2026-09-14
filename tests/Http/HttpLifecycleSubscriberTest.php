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

namespace Jblab\WideEvents\Tests\Http;

use Jblab\WideEvents\Core\InMemoryEventEmitter;
use Jblab\WideEvents\Core\WideEventContext;
use Jblab\WideEvents\Core\WideEventLimits;
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
        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new Response(status: 201)));

        self::assertCount(1, $destination->events());
        self::assertSame([
            'method'     => 'GET',
            'path'       => '/orders/42',
            'request_id' => 'req-42',
            'route'      => 'order_show',
        ], $destination->events()[0]->toArray()['request']);
        self::assertSame(201, $destination->events()[0]->toArray()['outcome']['http_status']);
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

    public function testExceptionsAreEmittedAndLaterResponseIsIgnored(): void
    {
        $destination = new InMemoryEventEmitter();
        $subscriber  = $this->subscriber($destination);
        $request     = Request::create('/broken');
        $kernel      = $this->createStub(HttpKernelInterface::class);

        $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $subscriber->onException(new \Symfony\Component\HttpKernel\Event\ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('not emitted'),
        ));
        $subscriber->onResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new Response()));

        self::assertCount(1, $destination->events());
        self::assertSame(\RuntimeException::class, $destination->events()[0]->toArray()['error']['class']);
    }

    private function subscriber(InMemoryEventEmitter $emitter): HttpLifecycleSubscriber
    {
        return new HttpLifecycleSubscriber(
            new WideEventContext(),
            $emitter,
            new WideEventLimits(),
            ['name' => 'test-service'],
        );
    }
}
