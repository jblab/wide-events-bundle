# This file is part of the Jblab Wide Events Bundle package.
# Copyright (c) 2026 Julien Bonnier <julien@jblab.io>
# SPDX-License-Identifier: Apache-2.0
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

default_version := "8.2"

set quiet

[doc("Display this help and exit")]
help:
    echo >&2 "\nJblab Wide Events Bundle\n\nUsage:\n just RECIPE [PARAMETERS]\n"
    just --list --unsorted
    echo >&2 ""

[doc("Clean temp/cache files")]
[script("bash")]
clean:
    rm -rf vendor/ && echo >&2 "vendor/ cleared"
    rm -f .php-cs-fixer.cache && echo >&2 ".php-cs-fixer.cache cleared"
    rm -f .phpunit.result.cache && echo >&2 ".phpunit.result.cache cleared"
    find .tools/ -name "vendor" -type d -prune -exec rm -rf {} \; -exec echo >&2 {}" cleared" \;

# ----------------------------------------------------------------------------------------------------------------------
# Shells
# ----------------------------------------------------------------------------------------------------------------------

[doc("Run a composer command in a PHP contaier")]
[group("shells")]
composer *args: (_run-local default_version "composer" args)

[doc("Run a php command in a PHP contaier")]
[group("shells")]
php *args: (_run-local default_version "php" args)

[doc("Open a shell in a PHP container")]
[group("shells")]
shell: (_run-local default_version "bash")

# ----------------------------------------------------------------------------------------------------------------------
# Tools
# ----------------------------------------------------------------------------------------------------------------------

[doc("Run project's test suite on supported PHP versions")]
[group("tools")]
test: (_test "8.2") (_test "8.3") (_test "8.4") (_test "8.5")

[doc("Run PHPStan on the project")]
[group("tools")]
stan: (_run-local default_version "composer tools:upgrade && composer tools:run:phpstan")

[doc("Run PHP Parallel Lint on the project")]
[group("tools")]
lint: (_run-local default_version "composer tools:upgrade && composer tools:run:php-lint")

[doc("Run PHP CS Fixer (check) on the project")]
[group("tools")]
cs: (_run-local default_version "composer tools:upgrade && composer tools:run:php-cs-fixer")

[doc("Run PHP CS Fixer (fix) on the project")]
[group("tools")]
cs-fix: (_run-local default_version "composer tools:upgrade && composer tools:run:php-cs-fixer:fix")

[doc("Run PHPStan, PHP Parallel Lint and PHP CodeSniffer on the project")]
[group("tools")]
run: (_run-local default_version "composer tools:upgrade && composer tools:run")

# ----------------------------------------------------------------------------------------------------------------------
# Helpers
# ----------------------------------------------------------------------------------------------------------------------

[private]
_build +version:
    docker build --build-arg PHP_VERSION={{version}} --tag jblab-wide-events:{{version}} --target tests .

[private]
_run version command *args: (_build version)
    docker run --rm jblab-wide-events:{{version}} bash -c "{{command}} {{args}}"

[private]
_run-local version command *args:
    docker run --rm --volume ".:/app" jblab-wide-events:{{version}} bash -c "{{command}} {{args}}"

[private]
_test version: && (_build version) (_run version "composer test")
    echo >&2 "Running tests on PHP {{version}}..."
