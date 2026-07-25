<?php

/**
 * Configuration for the Local Services Ads (LSA) lead dashboard module.
 *
 * LSA data is pulled from the main Google Ads API (the read-only
 * `local_services_lead` resources), which — unlike the standalone Local
 * Services API — is available for UK accounts. The OAuth/developer-token
 * credentials are shared with the rest of the app (see config/google_ads.php).
 */

return [
    // Client customer IDs (10 digits, no dashes) that run a Local Services
    // campaign and should be synced. Set LSA_CLIENT_CUSTOMER_IDS as a
    // comma-separated list, e.g. "1468333005,9987020611".
    'client_customer_ids' => array_values(array_filter(array_map(
        static fn ($id) => preg_replace('/\D/', '', trim((string) $id)),
        explode(',', (string) env('LSA_CLIENT_CUSTOMER_IDS', ''))
    ), static fn ($id) => strlen((string) $id) === 10)),

    // Rolling look-back window (days) re-synced on every run so that status
    // changes on existing leads are captured, not just brand-new leads.
    'sync_window_days' => (int) env('LSA_SYNC_WINDOW_DAYS', 35),

    // Safety cap on rows pulled per account per sync.
    'max_leads_per_account' => (int) env('LSA_MAX_LEADS_PER_ACCOUNT', 5000),
];
