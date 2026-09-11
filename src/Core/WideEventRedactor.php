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

/** Removes sensitive keys before event values are normalized or serialized. */
final class WideEventRedactor
{
    public const REDACTED = '[REDACTED]';

    /** @var list<string> */
    private const DEFAULT_KEYS = [
        'access_token',
        'api_key',
        'authorization',
        'client_secret',
        'cookie',
        'credit_card',
        'password',
        'private_key',
        'refresh_token',
        'secret',
        'set_cookie',
        'ssn',
        'token',
    ];

    /** @var array<string, true> */
    private readonly array $redactKeys;

    /** @var array<string, true> */
    private readonly array $allowedKeys;

    /**
     * @param list<string> $redactKeys  additional keys to redact
     * @param list<string> $allowedKeys keys retained when strict allow-list mode is enabled
     */
    public function __construct(
        array $redactKeys = [],
        array $allowedKeys = [],
        private readonly bool $strictAllowList = false,
    ) {
        $this->redactKeys  = $this->keySet([...self::DEFAULT_KEYS, ...$redactKeys]);
        $this->allowedKeys = $this->keySet($allowedKeys);

        if ($this->strictAllowList && [] === $this->allowedKeys) {
            throw new \InvalidArgumentException('Strict allow-list mode requires at least one allowed key.');
        }
    }

    /**
     * @param array<array-key, mixed> $payload
     *
     * @return array<array-key, mixed>
     */
    public function redact(array $payload): array
    {
        $redacted = [];
        foreach ($payload as $key => $value) {
            if ($this->strictAllowList && !isset($this->allowedKeys[$this->normalizeKey($key)])) {
                continue;
            }
            if (isset($this->redactKeys[$this->normalizeKey($key)])) {
                $redacted[$key] = self::REDACTED;
                continue;
            }
            if (\is_array($value)) {
                $redacted[$key] = $this->redact($value);
                continue;
            }
            $redacted[$key] = $value;
        }

        return $redacted;
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, true>
     */
    private function keySet(array $keys): array
    {
        $set = [];
        foreach ($keys as $key) {
            if ('' === $key) {
                throw new \InvalidArgumentException('Redaction keys cannot be empty.');
            }
            $set[$this->normalizeKey($key)] = true;
        }

        return $set;
    }

    private function normalizeKey(int|string $key): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '_', strtolower((string) $key));
    }
}
