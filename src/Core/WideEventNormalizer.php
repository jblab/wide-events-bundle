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

/** Converts supported PHP values into bounded, JSON-safe event data. */
final class WideEventNormalizer
{
    private readonly object $dropMarker;

    private int $fieldCount    = 0;
    private int $droppedFields = 0;
    private bool $truncated    = false;

    public function __construct()
    {
        $this->dropMarker = new \stdClass();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function normalize(array $payload, ?WideEventLimits $limits = null): array
    {
        $limits ??= new WideEventLimits();
        $this->fieldCount    = 0;
        $this->droppedFields = 0;
        $this->truncated     = false;

        $normalized = $this->normalizeArray($payload, 0, $limits);
        $normalized = $this->fitEvent($normalized, $limits);

        if ($this->truncated || $this->droppedFields > 0) {
            $meta = \is_array($normalized['meta'] ?? null) ? $normalized['meta'] : [];
            if ($this->truncated) {
                $meta['truncated'] = true;
            }
            if ($this->droppedFields > 0) {
                $meta['dropped_fields'] = $this->droppedFields;
            }
            $normalized['meta'] = $meta;
        }

        $encoded = json_encode($normalized, \JSON_THROW_ON_ERROR);
        if (\strlen($encoded) > $limits->maxEventBytes()) {
            throw new \LengthException('The normalized wide event exceeds the configured byte limit.');
        }

        return $normalized;
    }

    /**
     * @phpstan-impure
     *
     * @param array<array-key, mixed> $values
     *
     * @return array<mixed>
     */
    private function normalizeArray(array $values, int $depth, WideEventLimits $limits): array
    {
        $normalized = [];
        foreach ($values as $key => $value) {
            if ($this->fieldCount >= $limits->maxFields()) {
                $this->drop($limits, 'field');
                continue;
            }
            ++$this->fieldCount;
            $result = $this->normalizeValue($value, $depth + 1, $limits);
            if ($result !== $this->dropMarker) {
                $normalized[$key] = $result;
            }
        }

        return $normalized;
    }

    private function normalizeValue(mixed $value, int $depth, WideEventLimits $limits): mixed
    {
        if (\is_array($value)) {
            if ($depth >= $limits->maxDepth()) {
                return $this->oversized($limits, 'depth');
            }

            return $this->normalizeArray($value, $depth, $limits);
        }
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value)
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d\\TH:i:s.v\\Z');
        }
        if ($value instanceof \BackedEnum) {
            return $this->normalizeValue($value->value, $depth, $limits);
        }
        if ($value instanceof \UnitEnum) {
            return $value->name;
        }
        if ($value instanceof \JsonSerializable) {
            return $this->normalizeValue($value->jsonSerialize(), $depth, $limits);
        }
        if ($value instanceof \Stringable) {
            return $this->normalizeString((string) $value, $limits);
        }
        if (\is_string($value)) {
            return $this->normalizeString($value, $limits);
        }
        if (\is_int($value) || \is_bool($value) || null === $value) {
            return $value;
        }
        if (\is_float($value) && is_finite($value)) {
            return $value;
        }

        return $this->oversized($limits, 'value');
    }

    private function normalizeString(string $value, WideEventLimits $limits): mixed
    {
        if (\strlen($value) <= $limits->maxStringBytes()) {
            return $value;
        }
        if (WideEventLimits::STRATEGY_REJECT === $limits->oversizedValueStrategy()) {
            throw new \LengthException('A wide event string exceeds the configured byte limit.');
        }
        if (WideEventLimits::STRATEGY_DROP === $limits->oversizedValueStrategy()) {
            $this->drop($limits, 'string');

            return $this->dropMarker;
        }

        $this->truncated = true;

        return $this->truncateUtf8($value, $limits->maxStringBytes());
    }

    private function oversized(WideEventLimits $limits, string $kind): mixed
    {
        if (WideEventLimits::STRATEGY_REJECT === $limits->oversizedValueStrategy()) {
            throw new \LengthException(\sprintf('A wide event %s exceeds the configured limit.', $kind));
        }
        $this->drop($limits, $kind);

        return $this->dropMarker;
    }

    private function drop(WideEventLimits $limits, string $kind): void
    {
        if (WideEventLimits::STRATEGY_REJECT === $limits->oversizedValueStrategy()) {
            throw new \LengthException(\sprintf('A wide event %s exceeds the configured limit.', $kind));
        }
        ++$this->droppedFields;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function fitEvent(array $payload, WideEventLimits $limits): array
    {
        if (\strlen(json_encode($payload, \JSON_THROW_ON_ERROR)) <= $limits->maxEventBytes()) {
            return $payload;
        }
        if (WideEventLimits::STRATEGY_REJECT === $limits->oversizedValueStrategy()) {
            throw new \LengthException('The wide event exceeds the configured byte limit.');
        }

        foreach (array_keys($payload) as $key) {
            if ('meta' === $key) {
                continue;
            }
            unset($payload[$key]);
            ++$this->droppedFields;
            if (\strlen(json_encode($payload, \JSON_THROW_ON_ERROR)) <= $limits->maxEventBytes()) {
                return $payload;
            }
        }

        return $payload;
    }

    private function truncateUtf8(string $value, int $bytes): string
    {
        $value = substr($value, 0, $bytes);
        while ('' !== $value && false === preg_match('//u', $value)) {
            $value = substr($value, 0, -1);
        }

        return $value;
    }
}
