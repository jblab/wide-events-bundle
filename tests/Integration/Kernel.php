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

namespace Jblab\WideEvents\Tests\Integration;

use Jblab\WideEvents\Core\Emission\InMemoryEventEmitter;
use Jblab\WideEvents\JblabWideEventsBundle;
use Jblab\WideEvents\Tests\Integration\Http\HttpTestController;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new JblabWideEventsBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/jblab-wide-events-test/' . spl_object_hash($this);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test-secret',
            'test'   => true,
        ]);
        $container->extension('jblab_wide_events', [
            'enabled'  => true,
            'emitter'  => InMemoryEventEmitter::class,
            'service'  => ['name' => 'test-service'],
            'sampling' => ['sample_rate' => 1.0],
        ]);
        $container->services()->set(InMemoryEventEmitter::class);
        $container->services()->set(HttpTestController::class)->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes
            ->add('order_show', '/orders/{id}')
            ->controller(HttpTestController::class)
        ;
    }
}
