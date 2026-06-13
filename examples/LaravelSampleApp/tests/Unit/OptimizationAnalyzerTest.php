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

use App\Optimization\OptimizationAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the deterministic optimization rule engine against the real shipped
 * ruleset (resources/knowledge/rules.json).
 */
class OptimizationAnalyzerTest extends TestCase
{
    private function analyzer(): OptimizationAnalyzer
    {
        return OptimizationAnalyzer::fromJsonFile(
            __DIR__ . '/../../resources/knowledge/rules.json'
        );
    }

    /** @return array<string> the rule ids present in a set of findings */
    private function ids(array $findings): array
    {
        return array_map(static fn ($f) => $f['id'], $findings);
    }

    public function testFlagsMissingConversionTracking(): void
    {
        $findings = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => false,
            'optimizationScore' => 1.0,
            'campaigns' => [],
        ]);
        $this->assertContains('conversion-tracking-missing', $this->ids($findings));
    }

    public function testDoesNotFlagWhenConversionTrackingEnabled(): void
    {
        $findings = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => true,
            'optimizationScore' => 1.0,
            'campaigns' => [],
        ]);
        $this->assertNotContains('conversion-tracking-missing', $this->ids($findings));
    }

    public function testFlagsLowOptimizationScoreBelowThreshold(): void
    {
        $low = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => true,
            'optimizationScore' => 0.70,
            'campaigns' => [],
        ]);
        $this->assertContains('low-optimization-score', $this->ids($low));

        $high = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => true,
            'optimizationScore' => 0.92,
            'campaigns' => [],
        ]);
        $this->assertNotContains('low-optimization-score', $this->ids($high));
    }

    public function testFlagsBudgetLimitedCampaignAndNamesIt(): void
    {
        $findings = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => true,
            'optimizationScore' => 1.0,
            'campaigns' => [
                ['name' => 'Brand Search', 'budgetLostImpressionShare' => 0.22],
                ['name' => 'Generic Search', 'budgetLostImpressionShare' => 0.04],
            ],
        ]);

        $budget = array_values(array_filter($findings, static fn ($f) => $f['id'] === 'budget-limited'));
        $this->assertCount(1, $budget, 'Only the over-threshold campaign should fire.');
        $this->assertSame('Brand Search', $budget[0]['campaign']);
        $this->assertSame('warning', $budget[0]['severity']);
        $this->assertArrayHasKey('url', $budget[0]['source']);
    }

    public function testBooleanSignalRulesFire(): void
    {
        $findings = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => true,
            'optimizationScore' => 1.0,
            'campaigns' => [
                [
                    'name' => 'Search',
                    'ctrBelowBenchmark' => true,
                    'hasAdGroupWithSingleAd' => true,
                    'poorAdStrengthCount' => 3,
                ],
            ],
        ]);
        $ids = $this->ids($findings);
        $this->assertContains('low-ctr-vs-benchmark', $ids);
        $this->assertContains('single-ad-per-ad-group', $ids);
        $this->assertContains('poor-ad-strength', $ids);
    }

    public function testFlagsNewlyAddedRules(): void
    {
        $findings = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => true,
            'optimizationScore' => 1.0,
            'campaigns' => [
                [
                    'name' => 'Search',
                    'disapprovedAdCount' => 2,
                    'spendingWithoutConversions' => true,
                    'searchImpressionShare' => 0.31,
                ],
            ],
        ]);
        $ids = $this->ids($findings);
        $this->assertContains('disapproved-ads', $ids);
        $this->assertContains('spend-without-conversions', $ids);
        $this->assertContains('low-search-impression-share', $ids);

        // Severity of a disapproved-ads finding should be critical.
        $disapproved = array_values(array_filter($findings, static fn ($f) => $f['id'] === 'disapproved-ads'));
        $this->assertSame('critical', $disapproved[0]['severity']);
    }

    public function testHealthySearchImpressionShareDoesNotFlag(): void
    {
        $findings = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => true,
            'optimizationScore' => 1.0,
            'campaigns' => [
                ['name' => 'Search', 'searchImpressionShare' => 0.82, 'spendingWithoutConversions' => false, 'disapprovedAdCount' => 0],
            ],
        ]);
        $ids = $this->ids($findings);
        $this->assertNotContains('low-search-impression-share', $ids);
        $this->assertNotContains('spend-without-conversions', $ids);
        $this->assertNotContains('disapproved-ads', $ids);
    }

    public function testEveryRuleHasACitationAndReviewDate(): void
    {
        // Fire every rule at once and assert each finding is properly sourced.
        $findings = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => false,
            'optimizationScore' => 0.4,
            'campaigns' => [
                [
                    'name' => 'Everything',
                    'budgetLostImpressionShare' => 0.9,
                    'rankLostImpressionShare' => 0.9,
                    'ctrBelowBenchmark' => true,
                    'hasAdGroupWithSingleAd' => true,
                    'poorAdStrengthCount' => 5,
                    'disapprovedAdCount' => 1,
                    'spendingWithoutConversions' => true,
                    'searchImpressionShare' => 0.1,
                ],
            ],
        ]);
        $this->assertNotEmpty($findings);
        foreach ($findings as $f) {
            $this->assertArrayHasKey('source', $f);
            $this->assertArrayHasKey('url', $f['source']);
            $this->assertNotEmpty($f['source']['url']);
            $this->assertNotEmpty($f['reviewed']);
            $this->assertContains($f['severity'], ['critical', 'warning', 'info']);
        }
    }

    public function testHealthyAccountProducesNoFindings(): void
    {
        $findings = $this->analyzer()->analyze([
            'conversionTrackingEnabled' => true,
            'optimizationScore' => 0.97,
            'campaigns' => [
                [
                    'name' => 'Search',
                    'budgetLostImpressionShare' => 0.0,
                    'rankLostImpressionShare' => 0.05,
                    'ctrBelowBenchmark' => false,
                    'hasAdGroupWithSingleAd' => false,
                    'poorAdStrengthCount' => 0,
                ],
            ],
        ]);
        $this->assertSame([], $findings);
    }

    public function testIsDeterministic(): void
    {
        $signals = [
            'conversionTrackingEnabled' => false,
            'optimizationScore' => 0.5,
            'campaigns' => [
                ['name' => 'A', 'budgetLostImpressionShare' => 0.5, 'rankLostImpressionShare' => 0.5],
            ],
        ];
        $a = $this->analyzer()->analyze($signals);
        $b = $this->analyzer()->analyze($signals);
        $this->assertEquals($a, $b);
    }
}
