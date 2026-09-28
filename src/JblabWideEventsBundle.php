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

use Jblab\WideEvents\Core\Correlation\CorrelationProviderInterface;
use Jblab\WideEvents\Core\Emission\EventEmitterInterface;
use Jblab\WideEvents\Core\Emission\WideEventEmitter;
use Jblab\WideEvents\Core\Event\WideEventContext;
use Jblab\WideEvents\Core\Normalization\WideEventLimits;
use Jblab\WideEvents\Core\Normalization\WideEventRedactor;
use Jblab\WideEvents\Core\Sampling\SamplingPolicyInterface;
use Jblab\WideEvents\Core\Sampling\TailSamplingPolicy;
use Jblab\WideEvents\EventSubscriber\HttpLifecycleSubscriber;
use Jblab\WideEvents\Messenger\WideEventMiddleware;
use Jblab\WideEvents\OpenTelemetry\OpenTelemetryCorrelationProvider;
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

        if ($config['opentelemetry']['enabled'] && !class_exists(\OpenTelemetry\API\Trace\Span::class)) {
            throw new \InvalidArgumentException('OpenTelemetry correlation requires the open-telemetry/api package.');
        }

        $configurator->parameters()->set('jblab_wide_events.service_metadata', $config['service']);
        $services = $configurator->services();
        $services->set('jblab_wide_events.context', WideEventContext::class);
        $services->alias(WideEventContext::class, 'jblab_wide_events.context');
        $services->set('jblab_wide_events.limits', WideEventLimits::class)->args([
            $config['limits']['max_event_bytes'],
            $config['limits']['max_fields'],
            $config['limits']['max_depth'],
            $config['limits']['max_string_bytes'],
            $config['limits']['oversized_value_strategy'],
        ]);
        $services->set('jblab_wide_events.redactor', WideEventRedactor::class)->args([
            $config['redaction']['keys'],
            $config['redaction']['allowed_keys'],
            $config['redaction']['strict_allow_list'],
        ]);
        $services->set('jblab_wide_events.sampling_policy', TailSamplingPolicy::class)->args([
            $config['sampling']['sample_rate'],
            $config['sampling']['slow_event_threshold_ms'],
        ]);
        $services->set('jblab_wide_events.emitter', WideEventEmitter::class)->args([
            service($config['emitter']),
            service('jblab_wide_events.sampling_policy'),
        ]);
        $services->alias(EventEmitterInterface::class, 'jblab_wide_events.emitter');
        $services->alias(SamplingPolicyInterface::class, 'jblab_wide_events.sampling_policy');
        if ($config['opentelemetry']['enabled']) {
            $services->set('jblab_wide_events.opentelemetry_correlation_provider', OpenTelemetryCorrelationProvider::class);
            $services->alias(CorrelationProviderInterface::class, 'jblab_wide_events.opentelemetry_correlation_provider');
        }
        $openTelemetry = $config['opentelemetry']['enabled'] ? service('jblab_wide_events.opentelemetry_correlation_provider') : null;
        $services->set('jblab_wide_events.http_lifecycle_subscriber', HttpLifecycleSubscriber::class)->args([
            service('jblab_wide_events.context'),
            service('jblab_wide_events.emitter'),
            service('jblab_wide_events.limits'),
            $config['service'],
            $config['request_id']['propagate_response'],
            $openTelemetry,
        ])->tag('kernel.event_subscriber');
        $services->set('jblab_wide_events.messenger_middleware', WideEventMiddleware::class)->args([
            service('jblab_wide_events.context'),
            service('jblab_wide_events.emitter'),
            service('jblab_wide_events.limits'),
            $openTelemetry,
        ])->tag('messenger.middleware');
    }
}
