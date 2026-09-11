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

use Jblab\WideEvents\Core\WideEvent;
use Jblab\WideEvents\Core\WideEventContext;
use Jblab\WideEvents\Core\WideEventLimits;
use PHPUnit\Framework\TestCase;

final class WideEventTest extends TestCase
{
    public function testItCreatesTheVersionOneSchemaFromContext(): void
    {
        $context = new WideEventContext(['account_id' => 'acct-123']);
        $event   = WideEvent::fromContext(
            context: $context,
            event: 'http.request.completed',
            timestamp: new \DateTimeImmutable('2026-09-10T12:00:00+02:00'),
            service: ['name' => 'checkout'],
            request: ['method' => 'GET'],
        );

        self::assertSame([
            'schema_version' => 1,
            'event'          => 'http.request.completed',
            'timestamp'      => '2026-09-10T10:00:00.000Z',
            'service'        => ['name' => 'checkout'],
            'request'        => ['method' => 'GET'],
            'message'        => [],
            'outcome'        => [],
            'error'          => [],
            'context'        => ['account_id' => 'acct-123'],
            'meta'           => [],
        ], $event->toArray());
    }

    public function testContextDataCannotBecomeTopLevelEventFields(): void
    {
        $context = new WideEventContext([
            'event'   => 'application-value',
            'request' => ['private' => true],
        ]);

        $payload = WideEvent::fromContext($context, 'message.processed')->toArray();

        self::assertSame('message.processed', $payload['event']);
        self::assertSame('application-value', $payload['context']['event']);
        self::assertSame(['private' => true], $payload['context']['request']);
    }

    public function testEmptyEventNamesAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WideEvent::fromContext(new WideEventContext(), '');
    }

    public function testLimitsAreAppliedToTheFinalEvent(): void
    {
        $event = WideEvent::fromContext(
            context: new WideEventContext(['description' => 'abcdef']),
            event: 'test',
            limits: new WideEventLimits(maxStringBytes: 3),
        );

        self::assertSame('abc', $event->toArray()['context']['description']);
        self::assertTrue($event->toArray()['meta']['truncated']);
    }
}
