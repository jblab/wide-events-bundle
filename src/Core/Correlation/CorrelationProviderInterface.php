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

namespace Jblab\WideEvents\Core\Correlation;

/** Supplies optional active trace and span identifiers to event integrations. */
interface CorrelationProviderInterface
{
    /**
     * Return active correlation identifiers or an empty array when none are available.
     *
     * @return array{trace_id?: string, span_id?: string}
     */
    public function current(): array;
}
