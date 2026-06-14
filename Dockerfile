# Copyright 2020 Google LLC
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     https://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

# --- Stage 1: build the React/Inertia frontend (Vite) ---
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# --- Stage 2: serve the app over Apache ---
# A self-contained image that serves the Google Ads Team Dashboard.
FROM php:8.3-apache

# System packages and PHP extensions the app needs. Laravel requires mbstring
# (not bundled in the base image; needs libonig-dev); bcmath is used by the
# Google Ads library and zip speeds up Composer. gRPC is intentionally omitted —
# the library falls back to the REST transport.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libonig-dev curl \
    && docker-php-ext-install bcmath zip mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Point Apache at Laravel's public/ directory.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# Install PHP dependencies first (better layer caching), then copy the app.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --optimize-autoloader

COPY . /var/www/html
# Bring in the compiled frontend assets (and SSR bundle) from the build stage.
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY --from=frontend /app/bootstrap/ssr /var/www/html/bootstrap/ssr
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://localhost/health || exit 1
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
