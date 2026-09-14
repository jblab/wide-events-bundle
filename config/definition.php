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

use Jblab\WideEvents\Core\Normalization\WideEventLimits;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    // @formatter:off
    $definition->rootNode()
        ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->arrayNode('service')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('limits')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('max_event_bytes')->defaultValue(65_536)->min(1)->end()
                    ->integerNode('max_fields')->defaultValue(200)->min(1)->end()
                    ->integerNode('max_depth')->defaultValue(8)->min(1)->end()
                    ->integerNode('max_string_bytes')->defaultValue(4_096)->min(1)->end()
                    ->enumNode('oversized_value_strategy')
                        ->values([
                            WideEventLimits::STRATEGY_TRUNCATE,
                            WideEventLimits::STRATEGY_DROP,
                            WideEventLimits::STRATEGY_REJECT,
                        ])
                        ->defaultValue(WideEventLimits::STRATEGY_TRUNCATE)
                    ->end()
                ->end()
            ->end()
            ->arrayNode('redaction')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('keys')->scalarPrototype()->end()->defaultValue([])->end()
                    ->arrayNode('allowed_keys')->scalarPrototype()->end()->defaultValue([])->end()
                    ->booleanNode('strict_allow_list')->defaultFalse()->end()
                ->end()
            ->end()
            ->arrayNode('sampling')
                ->addDefaultsIfNotSet()
                ->children()
                    ->floatNode('sample_rate')->defaultValue(0.1)->min(0.0)->max(1.0)->end()
                    ->floatNode('slow_event_threshold_ms')->defaultValue(1_000)->min(0.000001)->end()
                ->end()
            ->end()
            ->scalarNode('emitter')->defaultNull()->end()
        ->end()
    ;
    // @formatter:on
};
