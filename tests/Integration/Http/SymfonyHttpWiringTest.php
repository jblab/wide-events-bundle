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
use Jblab\WideEvents\Tests\Integration\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

final class SymfonyHttpWiringTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testTheCompiledBundleHandlesARealHttpRequest(): void
    {
        $kernel   = self::bootKernel();
        $request  = Request::create('/orders/42', 'GET', server: ['HTTP_X_REQUEST_ID' => 'req-42']);
        $response = $kernel->handle($request);
        /** @var InMemoryEventEmitter $emitter */
        $emitter  = self::getContainer()->get(InMemoryEventEmitter::class);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('req-42', $response->headers->get('X-Request-Id'));
        self::assertCount(1, $emitter->events());
        self::assertSame('req-42', $emitter->events()[0]->toArray()['request']['request_id']);
        self::assertSame('/orders/42', $emitter->events()[0]->toArray()['request']['path']);
        self::assertSame(200, $emitter->events()[0]->toArray()['outcome']['http_status']);
    }
}
