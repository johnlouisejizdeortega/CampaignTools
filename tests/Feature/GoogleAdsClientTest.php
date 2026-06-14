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

namespace Tests\Feature;

use Google\Ads\GoogleAds\Lib\V24\GoogleAdsClient;
use Tests\TestCase;

/**
 * Verifies that the Google Ads client can be built purely from environment
 * variables (the Laravel Cloud path), with no google_ads_php.ini file present.
 */
class GoogleAdsClientTest extends TestCase
{
    public function testBuildsClientFromEnvironmentCredentials(): void
    {
        config([
            'google_ads.developer_token' => 'TEST_DEVELOPER_TOKEN',
            'google_ads.login_customer_id' => '1234567890',
            'google_ads.oauth2.client_id' => 'test-client-id',
            'google_ads.oauth2.client_secret' => 'test-client-secret',
            'google_ads.oauth2.refresh_token' => 'test-refresh-token',
            // Ensure the file fallback is not used.
            'app.google_ads_php_path' => null,
        ]);

        $client = $this->app->make(GoogleAdsClient::class);

        $this->assertInstanceOf(GoogleAdsClient::class, $client);
    }
}
