# Deploying to Laravel Cloud

The `deploy/laravel-cloud` branch promotes this dashboard to the **repository
root**, which is what Laravel Cloud needs to detect a Laravel application. (On
`main` / the feature branch the app lives in `examples/LaravelSampleApp/`, and
the repo root there is the Google Ads PHP *library*, so Laravel Cloud reports
"Unsupported framework".)

## Steps

1. In Laravel Cloud, **Create a new application** and import this repository.
2. Set the **branch** to `deploy/laravel-cloud`.
3. Laravel Cloud auto-detects Laravel and builds it (`composer install` plus
   `npm ci && npm run build`).
4. Set the environment variables below, then deploy.

## Environment variables

| Variable | Value | Purpose |
|---|---|---|
| `APP_KEY` | `base64:...` (Cloud can generate, or `php artisan key:generate --show`) | Session/cookie encryption |
| `APP_ENV` | `production` | Enables CSP + production behavior |
| `APP_DEBUG` | `false` | Don't leak errors |
| `TEAM_ACCESS_PASSWORD` | a strong shared password | Gates the dashboard for your team |
| `SESSION_DRIVER` | `cookie` | Stateless login that survives scaling (no DB needed) |
| `GOOGLE_ADS_DEVELOPER_TOKEN` | your developer token | **Enables the live API features** |
| `GOOGLE_ADS_CLIENT_ID` | OAuth2 client ID | " |
| `GOOGLE_ADS_CLIENT_SECRET` | OAuth2 client secret | " |
| `GOOGLE_ADS_REFRESH_TOKEN` | OAuth2 refresh token | " |
| `GOOGLE_ADS_LOGIN_CUSTOMER_ID` | manager (MCC) ID, no dashes | Only for manager accounts |

When `GOOGLE_ADS_DEVELOPER_TOKEN` is set, the app builds the Google Ads client
**entirely from these environment variables** — no `google_ads_php.ini` file is
needed. If they're left empty, the app falls back to a `google_ads_php.ini` file
at the project root (used for local development and the Docker image), and until
credentials are provided the live API calls fail gracefully while the cited
optimization playbook still works.

The app needs **no database** (cookie/file sessions, file cache). If Laravel
Cloud attaches one and runs migrations, that's harmless.

## Alternative: Docker (any host)

`docker compose up -d --build` serves the same app on port 8080 with a
health-checked container — see `TEAM_SETUP.md`. Works on Render, Railway,
Fly.io, a VPS, etc., and supports mounting `google_ads_php.ini` directly.
