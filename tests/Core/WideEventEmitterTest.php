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

namespace Jblab\WideEvents\Tests\Core;

use Jblab\WideEvents\Core\EventEmitterInterface;
use Jblab\WideEvents\Core\InMemoryEventEmitter;
use Jblab\WideEvents\Core\TailSamplingPolicy;
use Jblab\WideEvents\Core\WideEvent;
use Jblab\WideEvents\Core\WideEventContext;
use Jblab\WideEvents\Core\WideEventEmitter;
use PHPUnit\Framework\TestCase;

final class WideEventEmitterTest extends TestCase
{
    public function testItEmitsEventsAcceptedByTheSamplingPolicy(): void
    {
        $destination = new InMemoryEventEmitter();
        $emitter     = new WideEventEmitter($destination, new TailSamplingPolicy(sampleRate: 0.0));
        $event       = WideEvent::fromContext(new WideEventContext(), 'routine');

        $emitter->emit($event);

        self::assertSame([], $destination->events());
    }

    public function testItEmitsRetainedEvents(): void
    {
        $destination = new InMemoryEventEmitter();
        $emitter     = new WideEventEmitter($destination, new TailSamplingPolicy(sampleRate: 0.0));
        $event       = WideEvent::fromContext(
            context: new WideEventContext(),
            event: 'failed',
            error: ['class' => \RuntimeException::class],
        );

        $emitter->emit($event);

        self::assertSame([$event], $destination->events());
    }

    public function testEmitterFailuresAreContained(): void
    {
        $destination = new class implements EventEmitterInterface {
            public function emit(WideEvent $event): void
            {
                throw new \RuntimeException('transport unavailable');
            }
        };

        (new WideEventEmitter($destination))->emit(
            WideEvent::fromContext(new WideEventContext(), 'test'),
        );

        self::addToAssertionCount(1);
    }
}
