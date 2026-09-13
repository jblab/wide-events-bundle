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

use Jblab\WideEvents\Core\TailSamplingPolicy;
use Jblab\WideEvents\Core\WideEvent;
use Jblab\WideEvents\Core\WideEventContext;
use PHPUnit\Framework\TestCase;

final class TailSamplingPolicyTest extends TestCase
{
    public function testErrorsAndSlowEventsAreAlwaysRetained(): void
    {
        $policy = new TailSamplingPolicy(sampleRate: 0.0, slowEventThresholdMs: 500);
        $error  = WideEvent::fromContext(
            context: new WideEventContext(),
            event: 'failed',
            error: ['class' => \RuntimeException::class],
        );
        $slow   = WideEvent::fromContext(
            context: new WideEventContext(),
            event: 'slow',
            request: ['duration_ms' => 500],
        );

        self::assertTrue($policy->shouldSample($error));
        self::assertTrue($policy->shouldSample($slow));
    }

    public function testRoutineEventsRespectTheConfiguredRate(): void
    {
        $event = WideEvent::fromContext(new WideEventContext(), 'routine');

        self::assertFalse((new TailSamplingPolicy(sampleRate: 0.0))->shouldSample($event));
        self::assertTrue((new TailSamplingPolicy(sampleRate: 1.0))->shouldSample($event));
    }

    public function testInvalidSamplingConfigurationIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TailSamplingPolicy(sampleRate: 1.1);
    }
}
