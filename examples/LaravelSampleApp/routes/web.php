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

// Shared-password login flow that gates the application for team use. These
// routes are intentionally left outside the 'team.auth' middleware group.
Route::get('login', 'TeamAccessController@showLogin')->name('login');
Route::post('login', 'TeamAccessController@login')->name('login.submit');
Route::post('logout', 'TeamAccessController@logout')->name('logout');

// Application routes. Protected by the team password when TEAM_ACCESS_PASSWORD
// is configured (see App\Http\Middleware\TeamAuthenticate).
Route::middleware('team.auth')->group(function () {
    Route::get(
        '/',
        function () {
            return view('main');
        }
    );
    Route::post(
        'pause-campaign',
        'GoogleAdsApiController@pauseCampaignAction'
    );
    Route::match(
        ['get', 'post'],
        'show-report',
        'GoogleAdsApiController@showReportAction'
    );
    Route::post(
        'recommendations',
        'GoogleAdsApiController@showRecommendationsAction'
    );
});
