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

> All data comes straight from the Google Ads API in real time — nothing is
> stored in a database.

---

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

# 3. Create the local SQLite file (used only for sessions/cache; no MySQL needed)
touch database/database.sqlite

# 4. Add your Google Ads credentials (see next section)
cp ../Authentication/google_ads_php.ini ./google_ads_php.ini
#    ...then edit ./google_ads_php.ini and fill in the INSERT_..._HERE values

# 5. Start the app
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

## Hosting it for the team (Model A) — quick options

- **A small cloud VM** (e.g. a $5 VPS): install PHP 8.2 + Composer, run the setup
  steps, and serve behind Nginx with `php-fpm`. Point a subdomain at it and add
  HTTP basic auth or your SSO.
- **Any PHP host / platform** that supports Laravel 11 works. Set the document
  root to the `public/` directory.
- Whichever you pick: upload `google_ads_php.ini` to the server (don't commit
  it), set `APP_DEBUG=false`, and restrict access.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| `Class ...V24... not found` | Wrong library version installed. This app needs `googleads/google-ads-php ^33.0` (it ships API v20–v24). Re-run `composer update`. |
| `401 invalid_client` / `OAuth client was not found` | The credentials in `google_ads_php.ini` are still placeholders or wrong. |
| `Permission denied` writing logs | `chmod -R 775 storage bootstrap/cache` |
| Blank page / 500 error | Check `storage/logs/laravel.log`; ensure `php artisan key:generate` was run and `.env` exists. |
