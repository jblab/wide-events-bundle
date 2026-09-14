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

namespace Jblab\WideEvents\Core\Emission;

use Jblab\WideEvents\Core\Event\WideEvent;
use Jblab\WideEvents\Core\Sampling\SamplingPolicyInterface;

/** Applies sampling and prevents emission failures from breaking application flow. */
final class WideEventEmitter implements EventEmitterInterface
{
    public function __construct(
        private readonly EventEmitterInterface $emitter,
        private readonly ?SamplingPolicyInterface $samplingPolicy = null,
    ) {
    }

    public function emit(WideEvent $event): void
    {
        try {
            if (null !== $this->samplingPolicy && !$this->samplingPolicy->shouldSample($event)) {
                return;
            }

            $this->emitter->emit($event);
        } catch (\Throwable) {
            // Telemetry must never become an application failure.
        }
    }
}
