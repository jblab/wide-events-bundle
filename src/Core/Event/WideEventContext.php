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

namespace Jblab\WideEvents\Core\Event;

/**
 * Mutable application-owned data collected before an event is finalized.
 */
final class WideEventContext
{
    /** @var array<string, mixed> */
    private array $values;

    private bool $finalized = false;

    /**
     * @param array<array-key, mixed> $initial
     */
    public function __construct(array $initial = [])
    {
        $this->values = [];
        $this->merge($initial);
    }

    public function set(string $path, mixed $value): void
    {
        $this->assertMutable();
        $segments = $this->parsePath($path);
        $values   = &$this->values;

        foreach ($segments as $segment) {
            if (!isset($values[$segment]) || !\is_array($values[$segment]) || array_is_list($values[$segment])) {
                $values[$segment] = [];
            }

            $values = &$values[$segment];
        }

        $values = $value;
    }

    /**
     * Recursively merges associative arrays and replaces scalar conflicts.
     *
     * @param array<array-key, mixed> $values
     */
    public function merge(array $values): void
    {
        $this->assertMutable();

        foreach ($values as $path => $value) {
            if (!\is_string($path)) {
                throw new \InvalidArgumentException('Wide event context paths must be strings.');
            }

            $this->parsePath($path);
        }

        foreach ($values as $path => $value) {
            $this->mergeAtPath($this->parsePath($path), $value);
        }
    }

    /**
     * Add a duration in milliseconds under the application-owned timings map.
     */
    public function addTiming(string $name, int|float $milliseconds): void
    {
        $this->set('timings.' . $name, $milliseconds);
    }

    /**
     * Record safe error identity without storing a message or stack trace.
     */
    public function recordError(\Throwable|string $error, ?string $code = null, ?bool $retryable = null): void
    {
        $class = $error instanceof \Throwable ? $error::class : $error;
        if ('' === $class) {
            throw new \InvalidArgumentException('An error class cannot be empty.');
        }

        $record = ['class' => $class];
        if (null !== $code) {
            if ('' === $code) {
                throw new \InvalidArgumentException('An error code cannot be empty.');
            }

            $record['code'] = $code;
        }
        if (null !== $retryable) {
            $record['retryable'] = $retryable;
        }

        $this->assertMutable();
        $errors = $this->values['errors'] ?? [];
        if (!\is_array($errors) || !array_is_list($errors)) {
            $errors = [];
        }
        $errors[]               = $record;
        $this->values['errors'] = $errors;
    }

    /**
     * Mark the application outcome and optionally merge additional safe fields.
     *
     * @param array<string, mixed> $details
     */
    public function markOutcome(string $status, array $details = []): void
    {
        if ('' === $status) {
            throw new \InvalidArgumentException('An outcome status cannot be empty.');
        }

        $this->merge(['outcome' => $details]);
        $outcome = $this->values['outcome'] ?? [];
        if (!\is_array($outcome) || array_is_list($outcome)) {
            $outcome = [];
        }
        unset($outcome['status']);
        $this->values['outcome'] = ['status' => $status] + $outcome;
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
        $this->finalized = false;
        $this->values    = [];
    }

    /**
     * Return an immutable snapshot and reject further mutation until reset().
     *
     * @return array<string, mixed>
     */
    public function finalize(): array
    {
        $this->finalized = true;

        return $this->values;
    }

    /**
     * @return list<string>
     */
    private function parsePath(string $path): array
    {
        if ('' === $path || str_contains($path, "\0")) {
            throw new \InvalidArgumentException('A wide event context path cannot be empty or contain null bytes.');
        }

        $segments = explode('.', $path);
        foreach ($segments as $segment) {
            if ('' === $segment) {
                throw new \InvalidArgumentException('Wide event context paths cannot contain empty segments.');
            }
        }

        return $segments;
    }

    /**
     * @param list<string> $segments
     */
    private function mergeAtPath(array $segments, mixed $value): void
    {
        $path = array_shift($segments);
        if (null === $path) {
            return;
        }

        if ([] !== $segments) {
            $current = $this->values[$path] ?? [];
            if (!\is_array($current) || array_is_list($current)) {
                $current = [];
            }

            $this->values[$path] = $current;
            $values              = &$this->values[$path];
            $this->mergeInto($values, $segments, $value);

            return;
        }

        if (\is_array($value) && isset($this->values[$path]) && \is_array($this->values[$path])) {
            $this->values[$path] = $this->mergeArrays($this->values[$path], $value);

            return;
        }

        $this->values[$path] = $value;
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string>         $segments
     */
    private function mergeInto(array &$values, array $segments, mixed $value): void
    {
        $path = array_shift($segments);
        if (null === $path) {
            return;
        }
        if ([] !== $segments) {
            $current = $values[$path] ?? [];
            if (!\is_array($current) || array_is_list($current)) {
                $current = [];
            }
            $values[$path] = $current;
            $this->mergeInto($values[$path], $segments, $value);

            return;
        }

        if (\is_array($value) && isset($values[$path]) && \is_array($values[$path])) {
            $values[$path] = $this->mergeArrays($values[$path], $value);

            return;
        }
        $values[$path] = $value;
    }

    /**
     * @param array<mixed> $existing
     * @param array<mixed> $incoming
     *
     * @return array<mixed>
     */
    private function mergeArrays(array $existing, array $incoming): array
    {
        if (array_is_list($existing) || array_is_list($incoming)) {
            return $incoming;
        }

        foreach ($incoming as $key => $value) {
            if (\is_array($value) && isset($existing[$key]) && \is_array($existing[$key])) {
                $existing[$key] = $this->mergeArrays($existing[$key], $value);
            } else {
                $existing[$key] = $value;
            }
        }

        return $existing;
    }

    private function assertMutable(): void
    {
        if ($this->finalized) {
            throw new \LogicException('A finalized wide event context cannot be mutated; call reset() first.');
        }
    }
}
