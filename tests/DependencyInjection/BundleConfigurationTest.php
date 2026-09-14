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

namespace Jblab\WideEvents\Tests\DependencyInjection;

use Jblab\WideEvents\Core\EventEmitterInterface;
use Jblab\WideEvents\Core\InMemoryEventEmitter;
use Jblab\WideEvents\Core\WideEventContext;
use Jblab\WideEvents\Core\WideEventEmitter;
use Jblab\WideEvents\JblabWideEventsBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

final class BundleConfigurationTest extends TestCase
{
    public function testTheBundleIsInertWhenDisabled(): void
    {
        $container = $this->container();
        $this->extension()->load([['enabled' => false]], $container);

        $container->compile();

        self::assertFalse($container->has(WideEventContext::class));
    }

    public function testEnabledConfigurationRegistersCoreServices(): void
    {
        $container = $this->container();
        $container->register(InMemoryEventEmitter::class, InMemoryEventEmitter::class);
        $this->extension()->load([
            [
                'enabled' => true,
                'emitter' => InMemoryEventEmitter::class,
                'service' => ['name' => 'orders'],
            ],
        ], $container);

        $container->compile();

        self::assertInstanceOf(WideEventContext::class, $container->get(WideEventContext::class));
        self::assertInstanceOf(WideEventEmitter::class, $container->get(EventEmitterInterface::class));
        self::assertSame(['name' => 'orders'], $container->getParameter('jblab_wide_events.service_metadata'));
    }

    public function testEnabledConfigurationRequiresAnEmitter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An emitter service must be configured');

        $this->extension()->load([['enabled' => true]], $this->container());
    }

    private function extension(): ExtensionInterface
    {
        $extension = (new JblabWideEventsBundle())->getContainerExtension();
        if (!$extension instanceof ExtensionInterface) {
            throw new \LogicException('The wide events bundle must provide a container extension.');
        }

        return $extension;
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());

        return $container;
    }
}
