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

namespace Jblab\WideEvents\Tests\Unit\Monolog;

use Jblab\WideEvents\Core\Event\WideEvent;
use Jblab\WideEvents\Core\Event\WideEventContext;
use Jblab\WideEvents\Monolog\MonologEventEmitter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class MonologEventEmitterTest extends TestCase
{
    public function testItEmitsTheEventNameAndStructuredPayload(): void
    {
        $logger  = new RecordingLogger();
        $context = new WideEventContext();
        $context->set('order_id', 'order-123');
        $event = WideEvent::fromContext(
            context: $context,
            event: 'http.request.completed',
        );

        (new MonologEventEmitter($logger))->emit($event);

        self::assertSame(LogLevel::INFO, $logger->level);
        self::assertSame('http.request.completed', $logger->message);
        self::assertSame($event->toArray(), $logger->context);
    }
}

final class RecordingLogger implements LoggerInterface
{
    public string $level   = '';
    public string $message = '';
    /** @var array<string, mixed> */
    public array $context = [];

    /** @param array<string, mixed> $context */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->record(LogLevel::EMERGENCY, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->record(LogLevel::ALERT, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->record(LogLevel::CRITICAL, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->record(LogLevel::ERROR, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->record(LogLevel::WARNING, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->record(LogLevel::NOTICE, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->record(LogLevel::INFO, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->record(LogLevel::DEBUG, $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->record((string) $level, $message, $context);
    }

    /** @param array<string, mixed> $context */
    private function record(string $level, string|\Stringable $message, array $context): void
    {
        $this->level   = $level;
        $this->message = (string) $message;
        $this->context = $context;
    }
}
