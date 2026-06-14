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

use PHPUnit\Framework\TestCase;

/**
 * Guards the shipped knowledge datasets against drift and malformed entries.
 */
class RulesetContractTest extends TestCase
{
    /**
     * The exact set of signals produced by {@see \App\Optimization\AccountSignalsFetcher}.
     * If a rule references a signal not in this list, the fetcher would never
     * supply it and the rule could never fire — so the contract test fails,
     * forcing the two to stay in sync.
     */
    private const PRODUCED_SIGNALS = [
        // account scope
        'conversionTrackingEnabled',
        'optimizationScore',
        // campaign scope
        'ctr',
        'ctrBelowBenchmark',
        'searchImpressionShare',
        'spendingWithoutConversions',
        'budgetLostImpressionShare',
        'rankLostImpressionShare',
        'poorAdStrengthCount',
        'disapprovedAdCount',
        'hasAdGroupWithSingleAd',
    ];

    private const VALID_OPERATORS = ['is_true', 'is_false', 'gt', 'gte', 'lt', 'lte'];
    private const VALID_SEVERITIES = ['critical', 'warning', 'info'];

    /** @return array<int, array<string, mixed>> */
    private function rules(): array
    {
        $data = json_decode((string) file_get_contents(__DIR__ . '/../../resources/knowledge/rules.json'), true);
        return $data['rules'];
    }

    public function testEveryRuleIsWellFormed(): void
    {
        foreach ($this->rules() as $rule) {
            foreach (['id', 'scope', 'signal', 'operator', 'severity', 'title', 'why', 'fix', 'source', 'reviewed'] as $key) {
                $this->assertArrayHasKey($key, $rule, "Rule is missing \"$key\".");
            }
            $this->assertContains($rule['scope'], ['account', 'campaign']);
            $this->assertContains($rule['operator'], self::VALID_OPERATORS, "Rule {$rule['id']} has an invalid operator.");
            $this->assertContains($rule['severity'], self::VALID_SEVERITIES, "Rule {$rule['id']} has an invalid severity.");
            $this->assertMatchesRegularExpression('#^https://#', $rule['source']['url']);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $rule['reviewed']);
            if (in_array($rule['operator'], ['gt', 'gte', 'lt', 'lte'], true)) {
                $this->assertArrayHasKey('threshold', $rule, "Numeric rule {$rule['id']} needs a threshold.");
                $this->assertIsNumeric($rule['threshold']);
            }
        }
    }

    public function testEverySignalIsProducedByTheFetcher(): void
    {
        foreach ($this->rules() as $rule) {
            $this->assertContains(
                $rule['signal'],
                self::PRODUCED_SIGNALS,
                "Rule \"{$rule['id']}\" uses signal \"{$rule['signal']}\" that the fetcher does not produce."
            );
        }
    }

    public function testRuleIdsAreUnique(): void
    {
        $ids = array_map(static fn ($r) => $r['id'], $this->rules());
        $this->assertSame(array_unique($ids), $ids, 'Rule ids must be unique.');
    }

    public function testBenchmarkDatasetIsWellFormed(): void
    {
        $data = json_decode((string) file_get_contents(__DIR__ . '/../../resources/knowledge/benchmarks.json'), true);
        $this->assertArrayHasKey('industries', $data);
        $this->assertNotEmpty($data['industries']);
        foreach ($data['industries'] as $name => $metrics) {
            foreach (['avgCtr', 'avgCpc', 'avgCvr', 'avgCpa'] as $key) {
                $this->assertArrayHasKey($key, $metrics, "Industry \"$name\" missing \"$key\".");
                $this->assertIsNumeric($metrics[$key]);
                $this->assertGreaterThanOrEqual(0, $metrics[$key]);
            }
        }
    }
}
