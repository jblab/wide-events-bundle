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

/** Records emitted events without performing I/O. */
final class InMemoryEventEmitter implements EventEmitterInterface
{
    /** @var list<WideEvent> */
    private array $events = [];

    public function emit(WideEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<WideEvent>
     */
    public function events(): array
    {
        return $this->events;
    }
}
