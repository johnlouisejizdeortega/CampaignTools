<?php

/**
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
| Google Ads API credentials.
|
| When GOOGLE_ADS_DEVELOPER_TOKEN is set (e.g. on Laravel Cloud), the client is
| built from these environment variables — no file mounting required. Otherwise
| the app falls back to a google_ads_php.ini file at the project root (handy for
| local development and the Docker image). See App\Providers\AppServiceProvider.
*/

return [
    'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),

    // Required for manager accounts; the manager customer ID without dashes.
    'login_customer_id' => env('GOOGLE_ADS_LOGIN_CUSTOMER_ID'),

    'oauth2' => [
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),
    ],
];
