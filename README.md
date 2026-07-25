# Laravel Sample Application

This is a sample application demonstrating how to use Google Ads API in a Laravel
application.

## Preparations

We recommend using [Laravel Homestead](https://laravel.com/docs/8.x/homestead)
to satisfy all Laravel usage requirements. See
[this page](https://laravel.com/docs/8.x/homestead#installation-and-setup) for installation
instructions. If you're not using Homestead, make sure your web server has a document root set
to the location of this sample application.

Please make sure that your system fulfils all the
[requirements](https://github.com/googleads/google-ads-php/blob/master/README.md#requirements) of
the Google Ads API Client Library for PHP.

## Instructions

1.  Run `git clone https://github.com/googleads/google-ads-php.git` at the
    command prompt.
1.  You'll get a **google-ads-php** directory that contains this sample
    application. Navigate to it by running `cd
    google-ads-php/examples/LaravelSampleApp/`.
1.  Run `composer update` at the command prompt. This
    will install all the latest dependencies needed for running this application.
    **WARNING**: Make sure you are in the directory of this sample application.
    If you run those commands from the root directory of this library, it
    will install dependencies for the library instead.
1.  Follow the **Set up your OAuth2 credentials** instructions from the
    [Getting Started]((https://github.com/googleads/google-ads-php#getting-started)) documentation
    if you haven't set up the credentials yet.
1.  Copy your configured `google_ads_php.ini` to the same location as this `README.md` file.
1.  Run `php artisan serve`. Your sample app will be ready.
1.  You can now test the sample app by using the web browser at the URL you've
    set in your virtual machine or web server, e.g., `http://localhost:<PORT>`.

## Note

1.  Don't forget to follow instructions for
    [directory configuration](https://laravel.com/docs/8.x/installation#directory-configuration),
    or you'll face file permission errors when running this sample application.
1.  In case you turn on logging and specify the log file paths in
    `google_ads_php.ini`, make sure that your web server has permission to
    write to those files too.

## License

*   The Laravel framework is open-sourced software licensed under the [MIT
    license](https://opensource.org/licenses/MIT).
*   The Google Ads API PHP client library is open-sourced under the [Apache License
    2.0](https://github.com/googleads/google-ads-php/blob/master/LICENSE).

## Local Services Ads (LSA) lead dashboard

A lightweight, DB-backed view of Google **Local Services Ads** leads that bypasses
Google's slow LSA web dashboard. LSA data is pulled from the main Google Ads API
(the read-only `local_services_lead` resources, which work for UK accounts — the
standalone Local Services API is US/Canada-only). A scheduled job syncs into
MySQL; the UI reads only from the DB, so pages load instantly.

**Backend (implemented):**

1. **Configure** the accounts to sync (in addition to the existing
   `GOOGLE_ADS_*` credentials):
   ```env
   LSA_CLIENT_CUSTOMER_IDS=1468333005,9987020611   # 10 digits, no dashes
   LSA_SYNC_WINDOW_DAYS=365
   ```
   Each ID must run an active Local Services campaign.
2. **Migrate:** `php artisan migrate` (creates `lsa_leads`,
   `lsa_lead_conversations`, `lsa_daily_costs`, `lsa_sync_log`).
3. **First sync:** `php artisan lsa:sync` (or `--customer=1468333005` for one
   account). Scheduled automatically every 15 minutes.
4. **JSON API** (served entirely from the DB, behind the team-password gate):
   - `GET /api/leads` — paginated, filter by `customer_id`, `lead_status`,
     `charged`, `from`, `to`.
   - `GET /api/leads/{id}` — a lead with its conversation thread.
   - `GET /api/stats` — totals, charged count, spend, avg cost/lead, per-status.
   - `POST /api/leads/{id}/feedback` — the only write; proxies
     `ProvideLeadFeedback` (`survey_answer`: `VERY_SATISFIED`…`VERY_DISSATISFIED`).

Notes: the API exposes whether a lead was charged but not a per-lead amount, so
spend is derived from the LSA campaign's daily cost. LSA reporting can be ~30 min
stale on Google's side, hence the 15-minute cadence. The React inbox UI is the
next step.
