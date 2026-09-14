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

namespace Jblab\WideEvents\Core\Normalization;

/** Immutable bounds applied while producing a canonical event. */
final class WideEventLimits
{
    public const STRATEGY_TRUNCATE = 'truncate';
    public const STRATEGY_DROP     = 'drop';
    public const STRATEGY_REJECT   = 'reject';

    public function __construct(
        private readonly int $maxEventBytes = 65_536,
        private readonly int $maxFields = 200,
        private readonly int $maxDepth = 8,
        private readonly int $maxStringBytes = 4_096,
        private readonly string $oversizedValueStrategy = self::STRATEGY_TRUNCATE,
    ) {
        foreach ([
            'max event bytes'  => $maxEventBytes,
            'max fields'       => $maxFields,
            'max depth'        => $maxDepth,
            'max string bytes' => $maxStringBytes,
        ] as $name => $value) {
            if ($value < 1) {
                throw new \InvalidArgumentException(\sprintf('%s must be greater than zero.', ucfirst($name)));
            }
        }

        if (!\in_array($oversizedValueStrategy, [self::STRATEGY_TRUNCATE, self::STRATEGY_DROP, self::STRATEGY_REJECT], true)) {
            throw new \InvalidArgumentException('The oversized value strategy must be truncate, drop, or reject.');
        }
    }

    public function maxEventBytes(): int
    {
        return $this->maxEventBytes;
    }

    public function maxFields(): int
    {
        return $this->maxFields;
    }

    public function maxDepth(): int
    {
        return $this->maxDepth;
    }

    public function maxStringBytes(): int
    {
        return $this->maxStringBytes;
    }

    public function oversizedValueStrategy(): string
    {
        return $this->oversizedValueStrategy;
    }
}
