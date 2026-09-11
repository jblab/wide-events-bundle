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

namespace Jblab\WideEvents\Tests\Core;

use Jblab\WideEvents\Core\WideEventContext;
use PHPUnit\Framework\TestCase;

final class WideEventContextTest extends TestCase
{
    public function testItCanBeMutatedAndReset(): void
    {
        $context = new WideEventContext(['user' => ['id' => 42]]);
        $context->set('request_kind', 'interactive');
        $context->merge(['user' => ['role' => 'admin']]);

        self::assertSame([
            'user'         => ['id' => 42, 'role' => 'admin'],
            'request_kind' => 'interactive',
        ], $context->all());

        $context->reset();

        self::assertSame([], $context->all());
    }

    public function testEmptyKeysAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new WideEventContext())->set('', 'value');
    }

    public function testNestedPathsAndMergeCollisionsAreDeterministic(): void
    {
        $context = new WideEventContext([
            'user' => ['id' => 42, 'roles' => ['admin', 'support']],
        ]);

        $context->set('user.profile.name', 'Ada');
        $context->merge([
            'user' => [
                'id'    => 7,
                'roles' => ['customer'],
            ],
        ]);

        self::assertSame([
            'user' => [
                'id'      => 7,
                'roles'   => ['customer'],
                'profile' => ['name' => 'Ada'],
            ],
        ], $context->all());

        $context->set('user', 'anonymous');
        self::assertSame(['user' => 'anonymous'], $context->all());
    }

    public function testInvalidPathsDoNotPartiallyMutateTheContext(): void
    {
        $context = new WideEventContext(['existing' => true]);

        try {
            $context->merge(['valid' => 'value', 'invalid..path' => 'value']);
            self::fail('Expected an invalid path exception.');
        } catch (\InvalidArgumentException) {
            self::assertSame(['existing' => true], $context->all());
        }
    }

    public function testHelperMethodsWriteSafeApplicationData(): void
    {
        $context = new WideEventContext();
        $context->addTiming('database', 12.5);
        $context->recordError(new \RuntimeException('not serialized'), 'db_unavailable', true);
        $context->markOutcome('failure', ['retryable' => true, 'status' => 'ignored']);

        self::assertSame([
            'timings' => ['database' => 12.5],
            'errors'  => [[
                'class'     => \RuntimeException::class,
                'code'      => 'db_unavailable',
                'retryable' => true,
            ]],
            'outcome' => ['status' => 'failure', 'retryable' => true],
        ], $context->all());
    }

    public function testFinalizationCreatesASnapshotUntilReset(): void
    {
        $context  = new WideEventContext(['request_id' => 'req-1']);
        $snapshot = $context->finalize();

        self::assertSame(['request_id' => 'req-1'], $snapshot);

        $this->expectException(\LogicException::class);
        $context->set('request_id', 'req-2');
    }

    public function testResetReopensAndIsolatesTheContext(): void
    {
        $context = new WideEventContext(['request_id' => 'req-1']);
        $context->finalize();
        $context->reset();
        $context->set('request_id', 'req-2');

        self::assertSame(['request_id' => 'req-2'], $context->all());
    }
}
