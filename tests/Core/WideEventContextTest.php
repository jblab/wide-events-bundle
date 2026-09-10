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
}
