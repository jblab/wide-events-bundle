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

namespace Jblab\WideEvents\Monolog;

use Jblab\WideEvents\Core\Emission\EventEmitterInterface;
use Jblab\WideEvents\Core\Event\WideEvent;
use Psr\Log\LoggerInterface;

/** Emits structured wide events through a host-configured PSR-3 channel. */
final class MonologEventEmitter implements EventEmitterInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function emit(WideEvent $event): void
    {
        $payload = $event->toArray();

        $this->logger->info((string) $payload['event'], $payload);
    }
}
