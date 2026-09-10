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

$fileHeader = <<<'EOF'
    This file is part of the Jblab Wide Events Bundle package.

    Copyright (c) 2026 Julien Bonnier <julien@jblab.io>
    SPDX-License-Identifier: Apache-2.0

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    EOF;

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS3x0'             => true,
        '@PHP8x1Migration'       => true,
        '@PHP8x1Migration:risky' => true,
        '@Symfony'               => true,
        '@Symfony:risky'         => true,
        'header_comment'         => ['header' => $fileHeader],
        'php_unit_attributes'    => true,
        'concat_space'           => false,
        'declare_strict_types'   => true,
        'strict_comparison'      => true,
        'strict_param'           => true,
        'binary_operator_spaces' => ['operators' => [
            '='  => 'align_single_space',

            '=>' => 'align_single_space',
        ]],
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__ . '/src',
                __DIR__ . '/tests',
            ])
            ->append([__FILE__])
    )
;
