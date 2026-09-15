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

use Jblab\WideEvents\Messenger\WideEventCorrelationStamp;
use PHPUnit\Framework\TestCase;

final class WideEventCorrelationStampTest extends TestCase
{
    public function testItExposesOnlyPresentCorrelationIdentifiers(): void
    {
        $stamp = new WideEventCorrelationStamp('request-1', 'trace-1', null, 'cause-1');

        self::assertSame('request-1', $stamp->requestId());
        self::assertSame('trace-1', $stamp->traceId());
        self::assertNull($stamp->spanId());
        self::assertSame('cause-1', $stamp->causationId());
        self::assertSame([
            'request_id'   => 'request-1',
            'trace_id'     => 'trace-1',
            'causation_id' => 'cause-1',
        ], $stamp->toArray());
    }
}
