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

namespace Jblab\WideEvents\Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ComponentBoundariesTest extends TestCase
{
    /**
     * @param array<string> $forbidden
     */
    #[DataProvider('componentBoundaryProvider')]
    public function testComponentDoesNotReferenceForbiddenNamespaces(string $component, array $forbidden): void
    {
        $violations = [];
        $directory  = __DIR__ . '/../../../src/' . $component;

        foreach ($this->phpFiles($directory) as $file) {
            foreach ($this->referencedNamespaces($file) as $namespace) {
                foreach ($forbidden as $prefix) {
                    if (str_starts_with($namespace, $prefix)) {
                        $violations[] = \sprintf('%s references %s (forbidden prefix %s)', $file, $namespace, $prefix);
                    }
                }
            }
        }

        self::assertSame([], $violations, implode(\PHP_EOL, $violations));
    }

    public function testExtractionReadyComponentsHaveMatchingSourceAndTestDirectories(): void
    {
        $directories = [
            'Core'          => __DIR__ . '/../Core',
            'Messenger'     => __DIR__ . '/../Messenger',
            'Monolog'       => __DIR__ . '/../Monolog',
            'OpenTelemetry' => __DIR__ . '/../OpenTelemetry',
        ];

        foreach ($directories as $component => $testDirectory) {
            $sourceDirectory = __DIR__ . '/../../../src/' . $component;

            self::assertDirectoryExists($sourceDirectory);
            self::assertDirectoryExists($testDirectory);

            foreach ($this->phpFiles($sourceDirectory) as $file) {
                $contents = (string) file_get_contents($file);
                self::assertMatchesRegularExpression(
                    '/namespace Jblab\\\\WideEvents\\\\' . preg_quote($component, '/') . '(?:\\\\|;)/',
                    $contents,
                    $file . ' is outside its component namespace',
                );
            }
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function componentBoundaryProvider(): iterable
    {
        yield 'Core' => [
            'Core',
            [
                'Symfony\\',
                'Psr\\',
                'Jblab\\WideEvents\\EventSubscriber',
                'Jblab\\WideEvents\\Messenger',
                'Jblab\\WideEvents\\Monolog',
                'Jblab\\WideEvents\\OpenTelemetry',
            ],
        ];
        yield 'Messenger' => [
            'Messenger',
            [
                'Jblab\\WideEvents\\EventSubscriber',
                'Jblab\\WideEvents\\Monolog',
                'Jblab\\WideEvents\\OpenTelemetry',
                'Symfony\\Component\\Http',
                'Symfony\\Component\\HttpKernel',
            ],
        ];
        yield 'Monolog' => [
            'Monolog',
            [
                'Symfony\\',
                'Jblab\\WideEvents\\EventSubscriber',
                'Jblab\\WideEvents\\Messenger',
                'Jblab\\WideEvents\\OpenTelemetry',
            ],
        ];
        yield 'OpenTelemetry' => [
            'OpenTelemetry',
            [
                'Symfony\\',
                'Jblab\\WideEvents\\EventSubscriber',
                'Jblab\\WideEvents\\Messenger',
                'Jblab\\WideEvents\\Monolog',
            ],
        ];
    }

    /** @return array<string> */
    private function phpFiles(string $directory): array
    {
        $files    = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** @return array<string> */
    private function referencedNamespaces(string $file): array
    {
        $references = [];
        $tokens     = token_get_all((string) file_get_contents($file));
        $nameTokens = array_filter([
            \defined('T_NAME_QUALIFIED') ? \T_NAME_QUALIFIED : null,
            \defined('T_NAME_FULLY_QUALIFIED') ? \T_NAME_FULLY_QUALIFIED : null,
            \defined('T_NAME_RELATIVE') ? \T_NAME_RELATIVE : null,
        ], static fn (?int $token): bool => null !== $token);

        foreach ($tokens as $token) {
            if (\is_array($token) && \in_array($token[0], $nameTokens, true)) {
                $references[] = ltrim($token[1], '\\');
            }
        }

        return array_values(array_unique($references));
    }
}
