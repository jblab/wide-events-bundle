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

namespace Jblab\WideEvents\OpenTelemetry;

/** Reads safe correlation identifiers from the active OpenTelemetry span. */
final class OpenTelemetryCorrelationProvider
{
    /**
     * @return array{trace_id?: string, span_id?: string}
     */
    public function current(): array
    {
        if (!class_exists(\OpenTelemetry\API\Trace\Span::class)) {
            return [];
        }

        try {
            $context = \OpenTelemetry\API\Trace\Span::getCurrent()->getContext();
            if (!$context->isValid()) {
                return [];
            }

            return [
                'trace_id' => $context->getTraceId(),
                'span_id'  => $context->getSpanId(),
            ];
        } catch (\Throwable) {
            // Telemetry integrations must never become an application failure.
            return [];
        }
    }
}
