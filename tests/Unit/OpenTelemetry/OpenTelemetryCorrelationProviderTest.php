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

namespace Jblab\WideEvents\Tests\Unit\OpenTelemetry;

use Jblab\WideEvents\OpenTelemetry\OpenTelemetryCorrelationProvider;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryCorrelationProviderTest extends TestCase
{
    public function testItIsEmptyWhenOpenTelemetryIsNotInstalled(): void
    {
        if (class_exists(\OpenTelemetry\API\Trace\Span::class)) {
            self::markTestSkipped('The optional OpenTelemetry API is installed.');
        }

        self::assertSame([], (new OpenTelemetryCorrelationProvider())->current());
    }
}
