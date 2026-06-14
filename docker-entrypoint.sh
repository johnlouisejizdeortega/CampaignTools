#!/bin/sh
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

# First-run setup, then hand off to Apache.
set -e
cd /var/www/html

# Ensure an environment file exists.
[ -f .env ] || cp .env.example .env

# Generate an application key if one isn't set.
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# Sensible production defaults (overridable via the container environment).
: "${APP_ENV:=production}"
: "${APP_DEBUG:=false}"
export APP_ENV APP_DEBUG

# Create the SQLite file used for sessions/cache.
[ -f database/database.sqlite ] || touch database/database.sqlite

# If no real credentials file was mounted, drop in a placeholder so the app
# boots. The reporting/pause/optimization features need real credentials —
# mount your google_ads_php.ini to enable them (see TEAM_SETUP.md).
if [ ! -f google_ads_php.ini ]; then
    cat > google_ads_php.ini <<'INI'
[GOOGLE_ADS]
developerToken = "INSERT_DEVELOPER_TOKEN_HERE"
[OAUTH2]
clientId = "INSERT_OAUTH2_CLIENT_ID_HERE"
clientSecret = "INSERT_OAUTH2_CLIENT_SECRET_HERE"
refreshToken = "INSERT_OAUTH2_REFRESH_TOKEN_HERE"
INI
fi

php artisan config:clear || true
chown -R www-data:www-data storage bootstrap/cache database google_ads_php.ini 2>/dev/null || true

exec "$@"
