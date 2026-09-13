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

namespace Jblab\WideEvents\Core;

/** Retains important events and deterministically samples routine traffic. */
final class TailSamplingPolicy implements SamplingPolicyInterface
{
    public function __construct(
        private readonly float $sampleRate = 0.1,
        private readonly int|float $slowEventThresholdMs = 1_000,
    ) {
        if ($sampleRate < 0.0 || $sampleRate > 1.0) {
            throw new \InvalidArgumentException('The sampling rate must be between zero and one.');
        }
        if ($slowEventThresholdMs <= 0) {
            throw new \InvalidArgumentException('The slow event threshold must be greater than zero.');
        }
    }

    public function shouldSample(WideEvent $event): bool
    {
        $payload = $event->toArray();
        if ([] !== ($payload['error'] ?? [])) {
            return true;
        }

        $meta = \is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        if (true === ($meta['retain'] ?? false)) {
            return true;
        }

        $request = \is_array($payload['request'] ?? null) ? $payload['request'] : [];
        if (\is_int($request['duration_ms'] ?? null) || \is_float($request['duration_ms'] ?? null)) {
            if ($request['duration_ms'] >= $this->slowEventThresholdMs) {
                return true;
            }
        }

        if (0.0 === $this->sampleRate) {
            return false;
        }
        if (1.0 === $this->sampleRate) {
            return true;
        }

        $hash = hexdec(substr(hash('sha256', $this->samplingKey($payload)), 0, 12));

        return ($hash / 0xFFFFFFFFFFFF) < $this->sampleRate;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function samplingKey(array $payload): string
    {
        $identity = [
            'event'      => $payload['event'] ?? null,
            'request_id' => \is_array($payload['request'] ?? null) ? ($payload['request']['request_id'] ?? null) : null,
            'trace_id'   => \is_array($payload['request'] ?? null) ? ($payload['request']['trace_id'] ?? null) : null,
        ];

        return json_encode($identity, \JSON_THROW_ON_ERROR);
    }
}
