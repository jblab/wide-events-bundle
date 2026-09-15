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

namespace Jblab\WideEvents\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/** Carries only the identifiers needed to correlate a message. */
final class WideEventCorrelationStamp implements StampInterface
{
    public function __construct(
        private readonly ?string $requestId = null,
        private readonly ?string $traceId = null,
        private readonly ?string $spanId = null,
        private readonly ?string $causationId = null,
    ) {
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function traceId(): ?string
    {
        return $this->traceId;
    }

    public function spanId(): ?string
    {
        return $this->spanId;
    }

    public function causationId(): ?string
    {
        return $this->causationId;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $values = [];
        foreach ([
            'request_id'   => $this->requestId,
            'trace_id'     => $this->traceId,
            'span_id'      => $this->spanId,
            'causation_id' => $this->causationId,
        ] as $name => $value) {
            if (null !== $value) {
                $values[$name] = $value;
            }
        }

        return $values;
    }
}
