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

namespace App\Optimization;

use Google\Ads\GoogleAds\Lib\V24\GoogleAdsClient;
use Google\Ads\GoogleAds\V24\Enums\AdStrengthEnum\AdStrength;
use Google\Ads\GoogleAds\V24\Enums\ConversionTrackingStatusEnum\ConversionTrackingStatus;
use Google\Ads\GoogleAds\V24\Services\SearchGoogleAdsRequest;

/**
 * Collects the real account signals that {@see OptimizationAnalyzer} evaluates.
 * Each query is run defensively so a failure in one (e.g. ad-level data) still
 * leaves the rest of the signals usable.
 */
class AccountSignalsFetcher
{
    public function __construct(private GoogleAdsClient $client)
    {
    }

    /**
     * @param string $customerId the account ID without dashes
     * @return array<string, mixed> signals consumable by the analyzer
     */
    public function fetch(string $customerId): array
    {
        $signals = [
            'conversionTrackingEnabled' => null,
            'optimizationScore' => null,
            'account' => null,
            'campaigns' => [],
        ];

        $service = $this->client->getGoogleAdsServiceClient();

        // Account-level: optimization score and conversion tracking status.
        try {
            $response = $service->search(SearchGoogleAdsRequest::build(
                $customerId,
                'SELECT customer.optimization_score, '
                . 'customer.conversion_tracking_setting.conversion_tracking_status '
                . 'FROM customer LIMIT 1'
            ));
            foreach ($response->iterateAllElements() as $row) {
                $customer = $row->getCustomer();
                $signals['optimizationScore'] = $customer->hasOptimizationScore()
                    ? $customer->getOptimizationScore() : null;
                $status = $customer->getConversionTrackingSetting()?->getConversionTrackingStatus();
                $signals['conversionTrackingEnabled'] = $status !== null
                    && $status !== ConversionTrackingStatus::NOT_CONVERSION_TRACKED
                    && $status !== ConversionTrackingStatus::UNKNOWN
                    && $status !== ConversionTrackingStatus::UNSPECIFIED;
            }
        } catch (\Throwable $e) {
            // Leave account-level signals null; rules depending on them won't fire.
        }

        // Account-level aggregate metrics (last 30 days) for benchmark comparison.
        try {
            $response = $service->search(SearchGoogleAdsRequest::build(
                $customerId,
                'SELECT metrics.ctr, metrics.average_cpc, metrics.clicks, '
                . 'metrics.conversions, metrics.cost_micros '
                . 'FROM customer WHERE segments.date DURING LAST_30_DAYS'
            ));
            foreach ($response->iterateAllElements() as $row) {
                $m = $row->getMetrics();
                $clicks = $m->getClicks();
                $conversions = $m->getConversions();
                $cost = $m->getCostMicros() / 1_000_000;
                $signals['account'] = [
                    'ctr' => $m->getCtr(),
                    'cpc' => $m->getAverageCpc() / 1_000_000,
                    'cvr' => $clicks > 0 ? $conversions / $clicks : null,
                    'cpa' => $conversions > 0 ? $cost / $conversions : null,
                ];
            }
        } catch (\Throwable $e) {
            // No account aggregates; the benchmark panel simply won't render.
        }

        // Campaign-level metrics over the last 30 days.
        $campaigns = [];
        try {
            $response = $service->search(SearchGoogleAdsRequest::build(
                $customerId,
                'SELECT campaign.name, metrics.ctr, metrics.impressions, '
                . 'metrics.search_budget_lost_impression_share, '
                . 'metrics.search_rank_lost_impression_share '
                . 'FROM campaign WHERE segments.date DURING LAST_30_DAYS '
                . 'AND metrics.impressions > 0'
            ));
            $ctrSum = 0.0;
            $ctrCount = 0;
            foreach ($response->iterateAllElements() as $row) {
                $metrics = $row->getMetrics();
                $name = $row->getCampaign()->getName();
                $ctr = $metrics->getCtr();
                $ctrSum += $ctr;
                $ctrCount++;
                $campaigns[$name] = [
                    'name' => $name,
                    'ctr' => $ctr,
                    'budgetLostImpressionShare' => $metrics->getSearchBudgetLostImpressionShare(),
                    'rankLostImpressionShare' => $metrics->getSearchRankLostImpressionShare(),
                    'poorAdStrengthCount' => 0,
                    'hasAdGroupWithSingleAd' => false,
                ];
            }
            // Internal benchmark: flag campaigns whose CTR trails the account average.
            $accountAvgCtr = $ctrCount > 0 ? $ctrSum / $ctrCount : 0.0;
            foreach ($campaigns as &$campaign) {
                $campaign['ctrBelowBenchmark'] = $accountAvgCtr > 0
                    && $campaign['ctr'] < $accountAvgCtr;
            }
            unset($campaign);
        } catch (\Throwable $e) {
            // No campaign metrics available; analyzer simply finds nothing here.
        }

        // Ad-level signals: Ad Strength and single-ad ad groups.
        try {
            $adGroupAdCounts = [];
            $response = $service->search(SearchGoogleAdsRequest::build(
                $customerId,
                'SELECT campaign.name, ad_group.id, ad_group_ad.ad_strength '
                . "FROM ad_group_ad WHERE ad_group_ad.status != 'REMOVED'"
            ));
            foreach ($response->iterateAllElements() as $row) {
                $name = $row->getCampaign()->getName();
                if (!isset($campaigns[$name])) {
                    continue;
                }
                if ($row->getAdGroupAd()->getAdStrength() === AdStrength::POOR) {
                    $campaigns[$name]['poorAdStrengthCount']++;
                }
                $adGroupId = $row->getAdGroup()->getId();
                $adGroupAdCounts[$name][$adGroupId] = ($adGroupAdCounts[$name][$adGroupId] ?? 0) + 1;
            }
            foreach ($adGroupAdCounts as $name => $groups) {
                if (isset($campaigns[$name])) {
                    $campaigns[$name]['hasAdGroupWithSingleAd'] = in_array(1, $groups, true);
                }
            }
        } catch (\Throwable $e) {
            // Ad-level data unavailable; leave the defaults (0 / false).
        }

        $signals['campaigns'] = array_values($campaigns);
        return $signals;
    }
}
