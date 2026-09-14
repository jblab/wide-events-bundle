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

namespace Jblab\WideEvents;

use Jblab\WideEvents\Core\EventEmitterInterface;
use Jblab\WideEvents\Core\SamplingPolicyInterface;
use Jblab\WideEvents\Core\TailSamplingPolicy;
use Jblab\WideEvents\Core\WideEventContext;
use Jblab\WideEvents\Core\WideEventEmitter;
use Jblab\WideEvents\Core\WideEventLimits;
use Jblab\WideEvents\Core\WideEventRedactor;
use Jblab\WideEvents\EventSubscriber\HttpLifecycleSubscriber;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class JblabWideEventsBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        if (!$config['enabled']) {
            return;
        }

        if (null === $config['emitter'] || '' === $config['emitter']) {
            throw new \InvalidArgumentException('An emitter service must be configured when jblab_wide_events.enabled is true.');
        }

        $configurator->import('../config/services.php');
        $configurator->parameters()->set('jblab_wide_events.service_metadata', $config['service']);
        $services = $configurator->services();
        $services->set(WideEventContext::class)->class(WideEventContext::class)->public();
        $services->set(WideEventLimits::class)->class(WideEventLimits::class)->args([
            $config['limits']['max_event_bytes'],
            $config['limits']['max_fields'],
            $config['limits']['max_depth'],
            $config['limits']['max_string_bytes'],
            $config['limits']['oversized_value_strategy'],
        ]);
        $services->set(WideEventRedactor::class)->class(WideEventRedactor::class)->args([
            $config['redaction']['keys'],
            $config['redaction']['allowed_keys'],
            $config['redaction']['strict_allow_list'],
        ]);
        $services->set(TailSamplingPolicy::class)->class(TailSamplingPolicy::class)->args([
            $config['sampling']['sample_rate'],
            $config['sampling']['slow_event_threshold_ms'],
        ]);
        $services->set(WideEventEmitter::class)->class(WideEventEmitter::class)->args([
            service($config['emitter']),
            service(TailSamplingPolicy::class),
        ])->public();
        $services->alias(EventEmitterInterface::class, WideEventEmitter::class)->public();
        $services->alias(SamplingPolicyInterface::class, TailSamplingPolicy::class);
        $services->set(HttpLifecycleSubscriber::class)->class(HttpLifecycleSubscriber::class)->args([
            service(WideEventContext::class),
            service(EventEmitterInterface::class),
            service(WideEventLimits::class),
            $config['service'],
        ]);
    }
}
