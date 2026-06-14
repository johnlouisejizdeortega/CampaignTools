# Google Ads Team Dashboard — Setup Guide

A simple **web dashboard** (built on Laravel) that lets you and your teammates
view Google Ads campaign reports and pause campaigns from a browser — no command
line needed for day-to-day use.

It is built on top of the official
[Google Ads API Client Library for PHP](https://github.com/googleads/google-ads-php)
that lives in the root of this repository.

---

## What it does

| Feature | Description |
|---|---|
| **Team login** | A shared-password sign-in gates the whole app. Set `TEAM_ACCESS_PASSWORD` and share it with your team. |
| **Show Report** | Pull a live report for a Customer ID. Choose report type (campaign or customer), a date range (Yesterday, Last 7 days, Last week, Last month) and metrics (impressions, clicks, CTR). Results are paginated. |
| **Pause Campaign** | Pause a live campaign by entering its Customer ID + Campaign ID. |
| **Optimization suggestions** | Enter a Customer ID to fetch Google's **live recommendations** for the account, each explained in plain English with how to fix it — plus an always-on **optimization playbook** of common problems and fixes. |

The interface is a **React single-page app** built with
[Inertia.js](https://inertiajs.com/), [Tailwind CSS](https://tailwindcss.com/),
and [shadcn/ui](https://ui.shadcn.com/) components, served by Laravel. React
components live in `resources/js/` (pages in `resources/js/Pages`, shadcn
components in `resources/js/components/ui`), compiled by Vite.

> All data comes straight from the Google Ads API in real time — nothing is
> stored in a database.

---

## Where the optimization knowledge comes from (no AI)

The optimization suggestions are **deterministic and source-cited** — there is no
AI or randomness. Four knowledge sources feed the "Optimization suggestions" page:

1. **Google's Optimization Score** — Google's own validated estimate of account
   health, pulled live via the API.
2. **A deterministic rule engine** — `app/Optimization/OptimizationAnalyzer.php`
   evaluates a versioned ruleset (`resources/knowledge/rules.json`) against your
   real account signals (conversion tracking, impression share, Ad Strength, CTR
   vs. the account benchmark). Each rule cites an official Google source and a
   last-reviewed date. It is covered by unit tests
   (`tests/Unit/OptimizationAnalyzerTest.php`) so the same data always yields the
   same findings — run `vendor/bin/phpunit --testsuite Unit`.
3. **Google's own recommendations** — fetched live from the Google Ads
   RecommendationService.
4. **Industry benchmarks** — `resources/knowledge/benchmarks.json`, a
   *directional* third-party dataset (clearly labeled, with a source and review
   date). Refresh it periodically; it is not a guarantee.

To tune the system, edit `resources/knowledge/rules.json` (thresholds, copy,
sources) or `resources/knowledge/benchmarks.json` — no code changes required.

## Team login (shared password)

The app is gated by a **single shared password** so you can safely host it as one
link for the whole team — no per-user accounts or database required.

- Set `TEAM_ACCESS_PASSWORD` in your `.env` to any strong value. Everyone on the
  team uses that one password to sign in at `/login`.
- A **Sign out** button appears in the header once signed in.
- Leave `TEAM_ACCESS_PASSWORD` **empty** to disable the gate (handy for local
  development only — never do this on a hosted instance).

To change the password, edit `TEAM_ACCESS_PASSWORD` in `.env`, then run
`php artisan config:clear` and restart the app.

## How teammates access it (two models)

### Model A — One shared hosted instance (recommended for non-technical teams)

You (the owner) host the app **once** on a server. Teammates simply open the URL
in their browser. They never touch credentials or the command line.

- ✅ Easiest for teammates — just a link.
- ⚠️ Everyone who can reach the URL uses **your** Google Ads access and can pause
  live campaigns. **You must put it behind protection** (a login page, your
  company VPN, or IP allow-listing). Do **not** expose it openly on the internet.

### Model B — Each teammate runs it locally

Each teammate clones the repo and runs it on their own machine with their own
Google Ads credentials. More technical, but each person uses their own access.

Both models use the same setup steps below.

---

## Prerequisites

- **PHP 8.2+** and **Composer** ([install guide](https://getcomposer.org/))
- **Node.js 18+** and **npm** (to build the React frontend)
- Google Ads API credentials (see "Get your credentials" below)

---

## Setup steps

From this directory (`examples/LaravelSampleApp/`):

```bash
# 1. Install dependencies
#    (the C extensions grpc/bcmath are optional — the app runs over REST without them)
composer install --ignore-platform-req=ext-grpc --ignore-platform-req=ext-bcmath --ignore-platform-req=ext-protobuf

# 2. Create your environment file and an app key
cp .env.example .env
php artisan key:generate
#    Then edit .env and set a strong TEAM_ACCESS_PASSWORD so users must log in.

# 3. Build the React frontend
npm install
npm run build      # or `npm run dev` for hot reloading while developing

# 4. Create the local SQLite file (used only for sessions/cache; no MySQL needed)
touch database/database.sqlite

# 5. Add your Google Ads credentials (see next section)
cp ../Authentication/google_ads_php.ini ./google_ads_php.ini
#    ...then edit ./google_ads_php.ini and fill in the INSERT_..._HERE values

# 6. Start the app
php artisan serve
```

Then open **http://localhost:8000** in your browser.

---

## Get your credentials (one-time)

You need four things from Google. These are tied to a Google Ads account — they
can't be generated here.

1. **Developer token** — Google Ads Manager (MCC) account → Tools → API Center.
   [Guide](https://developers.google.com/google-ads/api/docs/first-call/dev-token)
2. **OAuth2 Client ID + Secret** — create an "installed application" OAuth client
   in the Google Cloud Console.
   [Guide](https://developers.google.com/google-ads/api/docs/oauth/cloud-project)
3. **Refresh token** — from the repo root, run:
   ```bash
   php examples/Authentication/GenerateUserCredentials.php
   ```
   Follow the prompts; copy the refresh token it prints.
4. **Customer ID** — your 10-digit Google Ads account number (no dashes). You
   enter this in the web form, not the config file.

Put items 1–3 into `google_ads_php.ini` (in this folder). If you manage multiple
accounts through a manager account, also uncomment and set `loginCustomerId`.

---

## Security notes (important for shared hosting)

- **Never commit secrets.** `google_ads_php.ini` and `.env` are git-ignored on
  purpose. Each deployment / teammate supplies their own.
- **This app can pause live campaigns.** If you host it for the team (Model A),
  put it behind authentication or a VPN. Anyone who reaches the URL can act on
  your Google Ads accounts.
- Set `APP_DEBUG=false` in `.env` for any shared/hosted deployment so error
  pages don't leak internal details.
- Known advisory: the pinned Laravel 11.x has a low-severity CRLF-in-email-rule
  issue (CVE-2026-48019) that is fixed in Laravel 12.60+. This app doesn't use
  the affected email validation rule, but upgrade the framework if you harden it
  for production.

---

## Hosting with Docker (recommended — one command)

The easiest way to make the dashboard available to everyone. Your teammates only
need the URL; the server runs in a container with no manual PHP/Composer setup.

On any machine with Docker installed:

```bash
cd examples/LaravelSampleApp

# 1. Put your configured credentials file next to docker-compose.yml
cp ../Authentication/google_ads_php.ini ./google_ads_php.ini
#    ...edit it and fill in your developer token + OAuth values.

# 2. Choose the team password (either edit docker-compose.yml, or:)
export TEAM_ACCESS_PASSWORD="your-strong-password"

# 3. Build and start
docker compose up -d --build
```

Then open **http://localhost:8080** (or `http://<server-ip>:8080`). To deploy for
the team, run this on a cloud VM and point a domain at it (ideally behind HTTPS).

- The credentials file is mounted **read-only** and never baked into the image.
- `APP_DEBUG` is `false` and `APP_ENV` is `production` by default.
- Update with `docker compose up -d --build`; stop with `docker compose down`.

## Hosting it for the team (Model A) — quick options

- **A small cloud VM** (e.g. a $5 VPS): install PHP 8.2 + Composer, run the setup
  steps, and serve behind Nginx with `php-fpm`. Point a subdomain at it and add
  HTTP basic auth or your SSO.
- **Any PHP host / platform** that supports Laravel 11 works. Set the document
  root to the `public/` directory.
- Whichever you pick: upload `google_ads_php.ini` to the server (don't commit
  it), set `APP_DEBUG=false`, and restrict access.

---

## Reliability & operations

This app is built to production standards:

- **Continuous integration** — `.github/workflows/dashboard-ci.yml` runs the PHP
  test suite, the frontend component tests, and a production build on every push
  and pull request that touches the app.
- **Type safety** — the frontend is **TypeScript** (React + Inertia); CI runs
  `npm run typecheck` (`tsc --noEmit`) to catch prop-shape and other type bugs
  before they ship.
- **Tests** — `vendor/bin/phpunit` (rule engine, contract tests, validation,
  throttling, security headers, the benchmarks command), `npm run test` (Vitest
  component tests), and `npm run test:e2e` (Playwright end-to-end of the real
  login/dashboard/confirm flows). A contract test keeps the ruleset and the data
  fetcher from drifting apart.
- **Input validation & graceful errors** — every action validates the Customer
  ID (and Campaign ID) and shows friendly messages instead of raw 500s; Google
  Ads API failures are caught and explained.
- **Safety** — pausing a campaign requires an explicit confirmation, and every
  pause is written to an append-only audit log (`storage/logs/audit.log`).
- **Security** — login attempts are rate-limited per IP, and every response
  carries hardening headers (CSP in production, `X-Frame-Options`, `nosniff`,
  `Referrer-Policy`, and HSTS over HTTPS).
- **Health check** — `GET /health` returns JSON for uptime monitors; the Docker
  image has a matching `HEALTHCHECK`.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `Vite manifest not found` / unstyled page | The frontend hasn't been built. Run `npm install && npm run build` (or `npm run dev`). |
| `Class ...V24... not found` | Wrong library version installed. This app needs `googleads/google-ads-php ^33.0` (it ships API v20–v24). Re-run `composer update`. |
| `401 invalid_client` / `OAuth client was not found` | The credentials in `google_ads_php.ini` are still placeholders or wrong. |
| `Permission denied` writing logs | `chmod -R 775 storage bootstrap/cache` |
| Blank page / 500 error | Check `storage/logs/laravel.log`; ensure `php artisan key:generate` was run and `.env` exists. |
