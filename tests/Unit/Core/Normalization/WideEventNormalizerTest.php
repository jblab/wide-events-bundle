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

namespace Jblab\WideEvents\Tests\Unit\Core\Normalization;

use Jblab\WideEvents\Core\Normalization\WideEventLimits;
use Jblab\WideEvents\Core\Normalization\WideEventNormalizer;
use Jblab\WideEvents\Core\Normalization\WideEventRedactor;
use PHPUnit\Framework\TestCase;

final class WideEventNormalizerTest extends TestCase
{
    public function testSupportedValuesBecomeJsonSafe(): void
    {
        $normalized = (new WideEventNormalizer())->normalize([
            'date'   => new \DateTimeImmutable('2026-09-11T12:00:00+02:00'),
            'enum'   => TestStatus::Ready,
            'object' => new TestStringable('value'),
            'number' => 1.5,
        ]);

        self::assertSame('2026-09-11T10:00:00.000Z', $normalized['date']);
        self::assertSame('ready', $normalized['enum']);
        self::assertSame('value', $normalized['object']);
        self::assertSame($normalized, json_decode(json_encode($normalized, \JSON_THROW_ON_ERROR), true));
    }

    public function testTruncationReportsSafeMetadata(): void
    {
        $normalized = (new WideEventNormalizer())->normalize(
            ['context' => ['description' => 'abcdef']],
            new WideEventLimits(maxStringBytes: 3),
        );

        self::assertSame('abc', $normalized['context']['description']);
        self::assertTrue($normalized['meta']['truncated']);
    }

    public function testDropAndRejectStrategiesAreSupported(): void
    {
        $normalizer = new WideEventNormalizer();
        $dropped    = $normalizer->normalize(
            ['context' => ['description' => 'abcdef']],
            new WideEventLimits(maxStringBytes: 3, oversizedValueStrategy: WideEventLimits::STRATEGY_DROP),
        );
        self::assertSame(['context' => [], 'meta' => ['dropped_fields' => 1]], $dropped);

        $this->expectException(\LengthException::class);
        $normalizer->normalize(
            ['context' => ['description' => 'abcdef']],
            new WideEventLimits(maxStringBytes: 3, oversizedValueStrategy: WideEventLimits::STRATEGY_REJECT),
        );
    }

    public function testFieldDepthAndEventByteLimitsAreApplied(): void
    {
        $normalized = (new WideEventNormalizer())->normalize(
            ['context' => ['first' => 'one', 'second' => 'two']],
            new WideEventLimits(maxFields: 2),
        );

        self::assertSame(['context' => ['first' => 'one'], 'meta' => ['dropped_fields' => 1]], $normalized);
        self::assertLessThanOrEqual(100, \strlen(json_encode($normalized, \JSON_THROW_ON_ERROR)));
    }

    public function testSensitiveKeysAreRedactedRecursivelyBeforeNormalization(): void
    {
        $normalized = (new WideEventNormalizer())->normalize(
            ['context' => ['password' => 'secret', 'nested' => [['token' => 'value', 'safe' => true]]]],
            redactor: new WideEventRedactor(),
        );

        self::assertSame([
            'context' => [
                'password' => WideEventRedactor::REDACTED,
                'nested'   => [['token' => WideEventRedactor::REDACTED, 'safe' => true]],
            ],
        ], $normalized);
    }

    public function testCustomKeysAndStrictAllowListAreSupported(): void
    {
        $normalized = (new WideEventNormalizer())->normalize(
            ['context' => ['customer_id' => 'customer-1', 'internal' => true, 'profile' => ['name' => 'Ada']]],
            redactor: new WideEventRedactor(
                redactKeys: ['customer_id'],
                allowedKeys: ['context', 'customer_id', 'profile', 'name'],
                strictAllowList: true,
            ),
        );

        self::assertSame([
            'context' => [
                'customer_id' => WideEventRedactor::REDACTED,
                'profile'     => ['name' => 'Ada'],
            ],
        ], $normalized);
    }
}

enum TestStatus: string
{
    case Ready = 'ready';
}

final readonly class TestStringable implements \Stringable
{
    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
