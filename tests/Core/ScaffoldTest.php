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

namespace Jblab\WideEvents\Tests;

use Jblab\WideEvents\JblabWideEventsBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ScaffoldTest extends TestCase
{
    public function testExtractionReadyComponentDirectoriesExist(): void
    {
        foreach (['Core', 'Monolog', 'Messenger', 'OpenTelemetry'] as $component) {
            self::assertDirectoryExists(__DIR__ . '/../../src/' . $component);
            self::assertDirectoryExists(__DIR__ . '/../' . $component);
        }
    }

    public function testSymfonyBundleEntryPointIsAvailable(): void
    {
        self::assertInstanceOf(Bundle::class, new JblabWideEventsBundle());
    }
}
