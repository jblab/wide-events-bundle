# This file is part of the Jblab Wide Events Bundle package.
# Copyright (c) 2026 Julien Bonnier <julien@jblab.io>
# SPDX-License-Identifier: Apache-2.0
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
ARG PHP_VERSION=8.2
# ----------------------------------------------------------------------------------------------------------------------
# BASE IMAGE
# ----------------------------------------------------------------------------------------------------------------------
FROM php:${PHP_VERSION}-alpine AS base

ARG APP_UID=1000
ARG APP_GID=1000
ENV APP_USER=app
ENV APP_DIR=/app

RUN set -eux; \
    addgroup -g $APP_GID -S $APP_USER; \
    adduser -S -D -h "/home/$APP_USER" -u $APP_UID -s /sbin/nologin -G $APP_USER -g $APP_USER $APP_USER

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN set -e; \
    apk add --no-cache git bash

RUN install-php-extensions @composer

USER $APP_USER
WORKDIR $APP_DIR

# ----------------------------------------------------------------------------------------------------------------------
# TESTS IMAGE
# ----------------------------------------------------------------------------------------------------------------------
FROM base AS tests

COPY --chown="${APP_USER}:${APP_USER}" . "$APP_DIR"

RUN set -e; \
    composer install;
