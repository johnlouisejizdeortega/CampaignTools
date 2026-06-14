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

namespace Tests\Unit;

use App\Http\Controllers\GoogleAdsApiController;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that the report field list is built from a strict allowlist so that
 * nothing arbitrary can be injected into the GAQL query.
 */
class ReportFieldsTest extends TestCase
{
    public function testKeepsDefaultsAndAllowedMetrics(): void
    {
        $fields = GoogleAdsApiController::selectFieldsFor('campaign', [
            'impressions' => 'metrics.impressions',
            'clicks' => 'metrics.clicks',
        ]);
        $this->assertSame(
            ['campaign.id', 'campaign.name', 'campaign.status', 'metrics.impressions', 'metrics.clicks'],
            $fields
        );
    }

    public function testDropsInjectedFields(): void
    {
        $fields = GoogleAdsApiController::selectFieldsFor('campaign', [
            'evil' => 'campaign.id FROM customer WHERE 1=1',
            'ctr' => 'metrics.ctr',
            'also_evil' => 'metrics.cost_micros, campaign.name',
        ]);
        $this->assertSame(['campaign.id', 'campaign.name', 'campaign.status', 'metrics.ctr'], $fields);
        $this->assertNotContains('campaign.id FROM customer WHERE 1=1', $fields);
        $this->assertNotContains('metrics.cost_micros, campaign.name', $fields);
    }

    public function testCustomerReportUsesItsDefaults(): void
    {
        $this->assertSame(['customer.id'], GoogleAdsApiController::selectFieldsFor('customer', []));
    }
}
