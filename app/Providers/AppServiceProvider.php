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

namespace App\Providers;

use Google\Ads\GoogleAds\Lib\V24\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Binds the Google Ads API client. Credentials come from environment
        // variables when a developer token is configured (e.g. Laravel Cloud),
        // otherwise from a google_ads_php.ini file at the project root (local
        // development and the Docker image).
        $this->app->singleton('Google\Ads\GoogleAds\Lib\V24\GoogleAdsClient', function () {
            $developerToken = config('google_ads.developer_token');

            if (!empty($developerToken)) {
                $oAuth2Credential = (new OAuth2TokenBuilder())
                    ->withClientId(config('google_ads.oauth2.client_id'))
                    ->withClientSecret(config('google_ads.oauth2.client_secret'))
                    ->withRefreshToken(config('google_ads.oauth2.refresh_token'))
                    ->build();

                $clientBuilder = (new GoogleAdsClientBuilder())
                    ->withDeveloperToken($developerToken)
                    // Use the REST transport. On some managed hosts the gRPC
                    // call-credentials plugin fails to attach the OAuth access
                    // token, causing UNAUTHENTICATED errors even though the token
                    // is valid; REST sends it as a standard Authorization header.
                    ->withTransport('rest')
                    ->withOAuth2Credential($oAuth2Credential);

                if (!empty(config('google_ads.login_customer_id'))) {
                    $clientBuilder->withLoginCustomerId(config('google_ads.login_customer_id'));
                }

                return $clientBuilder->build();
            }

            // Falls back to the properties file.
            return (new GoogleAdsClientBuilder())
                ->fromFile(config('app.google_ads_php_path'))
                ->withOAuth2Credential((new OAuth2TokenBuilder())
                    ->fromFile(config('app.google_ads_php_path'))
                    ->build())
                ->build();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Emit pagination markup (ul.pagination > li.active/disabled) that the
        // dashboard's CSS styles.
        Paginator::useBootstrapFour();
    }
}
