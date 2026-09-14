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

namespace Jblab\WideEvents\Core\Sampling;

use Jblab\WideEvents\Core\Event\WideEvent;

/** Decides whether a finalized wide event should be emitted. */
interface SamplingPolicyInterface
{
    public function shouldSample(WideEvent $event): bool;
}
