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

use InvalidArgumentException;

/**
 * A deterministic, source-cited rule engine for Google Ads optimization. It
 * evaluates a ruleset (loaded from a JSON dataset) against an account's real
 * signals and returns findings. There is no AI or randomness involved: the same
 * signals always produce the same findings, which makes the engine auditable
 * and unit-testable.
 */
class OptimizationAnalyzer
{
    /**
     * @param array<int, array<string, mixed>> $rules the declarative ruleset
     */
    public function __construct(private array $rules)
    {
    }

    /**
     * Builds an analyzer from a rules JSON dataset (see resources/knowledge/rules.json).
     *
     * @param string $path absolute path to the rules JSON file
     * @return self the configured analyzer
     */
    public static function fromJsonFile(string $path): self
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded) || !isset($decoded['rules']) || !is_array($decoded['rules'])) {
            throw new InvalidArgumentException("Invalid rules dataset at: $path");
        }
        return new self($decoded['rules']);
    }

    /**
     * Evaluates every rule against the supplied account signals.
     *
     * Expected $signals shape:
     *  [
     *    'conversionTrackingEnabled' => bool,
     *    'optimizationScore' => float|null,   // 0..1
     *    'campaigns' => [
     *       ['name' => string, '<signal>' => mixed, ...],
     *    ],
     *  ]
     *
     * @param array<string, mixed> $signals the account signals
     * @return array<int, array<string, mixed>> the findings, each containing the
     *     rule metadata plus the matched campaign (or null) and observed value
     */
    public function analyze(array $signals): array
    {
        $findings = [];
        $campaigns = $signals['campaigns'] ?? [];

        foreach ($this->rules as $rule) {
            $scope = $rule['scope'] ?? 'account';

            if ($scope === 'account') {
                $value = $signals[$rule['signal']] ?? null;
                if ($this->matches($rule, $value)) {
                    $findings[] = $this->finding($rule, null, $value);
                }
                continue;
            }

            // Campaign-scoped: evaluate each campaign independently.
            foreach ($campaigns as $campaign) {
                $value = $campaign[$rule['signal']] ?? null;
                if ($this->matches($rule, $value)) {
                    $findings[] = $this->finding($rule, $campaign['name'] ?? null, $value);
                }
            }
        }

        return $findings;
    }

    /**
     * Determines whether a single observed value satisfies a rule's condition.
     *
     * @param array<string, mixed> $rule the rule definition
     * @param mixed $value the observed signal value
     * @return bool true when the rule fires
     */
    private function matches(array $rule, mixed $value): bool
    {
        $operator = $rule['operator'] ?? null;
        $threshold = $rule['threshold'] ?? null;

        return match ($operator) {
            'is_true' => $value === true,
            'is_false' => $value === false,
            'gt' => is_numeric($value) && $value > $threshold,
            'gte' => is_numeric($value) && $value >= $threshold,
            'lt' => is_numeric($value) && $value < $threshold,
            'lte' => is_numeric($value) && $value <= $threshold,
            default => throw new InvalidArgumentException("Unknown operator: " . var_export($operator, true)),
        };
    }

    /**
     * Shapes a finding for the UI from a matched rule.
     *
     * @param array<string, mixed> $rule the rule definition
     * @param string|null $campaign the matched campaign name, if campaign-scoped
     * @param mixed $value the observed signal value
     * @return array<string, mixed> the finding
     */
    private function finding(array $rule, ?string $campaign, mixed $value): array
    {
        return [
            'id' => $rule['id'],
            'title' => $rule['title'],
            'severity' => $rule['severity'],
            'why' => $rule['why'],
            'fix' => $rule['fix'],
            'source' => $rule['source'],
            'reviewed' => $rule['reviewed'] ?? null,
            'campaign' => $campaign,
            'observed' => $value,
        ];
    }
}
