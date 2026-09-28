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

namespace Jblab\WideEvents\Tests\Integration\DependencyInjection;

use Jblab\WideEvents\JblabWideEventsBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BundleBootTest extends TestCase
{
    public function testBundleCanBuildAContainer(): void
    {
        $container = new ContainerBuilder();
        $bundle    = new JblabWideEventsBundle();

        $bundle->build($container);

        $container->compile();

        self::assertTrue($container->isCompiled());
    }
}
