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

/**
 * Mutable application-owned data collected before an event is finalized.
 */
final class WideEventContext
{
    /** @var array<string, mixed> */
    private array $values;

    /**
     * @param array<string, mixed> $initial
     */
    public function __construct(array $initial = [])
    {
        $this->values = $initial;
    }

    public function set(string $key, mixed $value): void
    {
        $this->assertKey($key);
        $this->values[$key] = $value;
    }

    /**
     * Recursively merges associative arrays and replaces scalar conflicts.
     *
     * @param array<string, mixed> $values
     */
    public function merge(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->assertKey($key);

            if (\is_array($value) && isset($this->values[$key]) && \is_array($this->values[$key])) {
                $this->values[$key] = array_replace_recursive($this->values[$key], $value);
                continue;
            }

            $this->values[$key] = $value;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    public function reset(): void
    {
        $this->values = [];
    }

    private function assertKey(string $key): void
    {
        if ('' === $key) {
            throw new \InvalidArgumentException('A wide event context key cannot be empty.');
        }
    }
}
