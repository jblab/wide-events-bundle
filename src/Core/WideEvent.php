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
 * Immutable, finalized canonical wide event.
 */
final class WideEvent
{
    public const SCHEMA_VERSION = 1;

    /** @var list<string> */
    public const RESERVED_ROOTS = [
        'event',
        'timestamp',
        'service',
        'request',
        'message',
        'outcome',
        'error',
        'context',
        'meta',
    ];

    /** @var array<string, mixed> */
    private readonly array $payload;

    /**
     * @param array<string, mixed> $service
     * @param array<string, mixed> $request
     * @param array<string, mixed> $message
     * @param array<string, mixed> $outcome
     * @param array<string, mixed> $error
     * @param array<string, mixed> $meta
     *
     * @throws \Exception
     */
    public static function fromContext(
        WideEventContext $context,
        string $event,
        ?\DateTimeInterface $timestamp = null,
        array $service = [],
        array $request = [],
        array $message = [],
        array $outcome = [],
        array $error = [],
        array $meta = [],
        ?WideEventLimits $limits = null,
    ): self {
        if ('' === $event) {
            throw new \InvalidArgumentException('A wide event name cannot be empty.');
        }

        $timestamp ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $timestamp = $timestamp->setTimezone(new \DateTimeZone('UTC'));

        $payload = [
            'schema_version' => self::SCHEMA_VERSION,
            'event'          => $event,
            'timestamp'      => $timestamp->format('Y-m-d\\TH:i:s.v\\Z'),
            'service'        => $service,
            'request'        => $request,
            'message'        => $message,
            'outcome'        => $outcome,
            'error'          => $error,
            'context'        => $context->finalize(),
            'meta'           => $meta,
        ];

        return new self((new WideEventNormalizer())->normalize($payload, $limits));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
