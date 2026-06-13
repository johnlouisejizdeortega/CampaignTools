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

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Validates and, optionally, refreshes the industry benchmark dataset
 * (resources/knowledge/benchmarks.json).
 *
 *   php artisan benchmarks:refresh                 # validate only (no writes)
 *   php artisan benchmarks:refresh --source=new.json   # ingest new numbers + restamp
 *   php artisan benchmarks:refresh --date=2026-07-01   # restamp the review date
 *
 * Keeping the benchmark data honest is the whole point: the command never
 * silently restamps the "reviewed" date unless you actually refresh or ask it to.
 */
class RefreshBenchmarks extends Command
{
    protected $signature = 'benchmarks:refresh
        {--source= : Path to a JSON file of updated {industries:{...}} benchmark numbers}
        {--date= : Review date to stamp (YYYY-MM-DD); defaults to today when refreshing}';

    protected $description = 'Validate and optionally refresh the industry benchmark dataset';

    /** The metric keys every industry entry must provide. */
    private const REQUIRED_METRICS = ['avgCtr', 'avgCpc', 'avgCvr', 'avgCpa'];

    public function handle(): int
    {
        $path = resource_path('knowledge/benchmarks.json');
        if (!is_file($path)) {
            $this->error("Benchmark dataset not found at: $path");
            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || !isset($data['industries']) || !is_array($data['industries'])) {
            $this->error('Benchmark dataset is malformed (missing "industries").');
            return self::FAILURE;
        }

        $willWrite = false;

        // Optionally merge in an updated dataset.
        if ($source = $this->option('source')) {
            if (!is_file($source)) {
                $this->error("Source file not found: $source");
                return self::FAILURE;
            }
            $incoming = json_decode((string) file_get_contents($source), true);
            if (!is_array($incoming) || !isset($incoming['industries']) || !is_array($incoming['industries'])) {
                $this->error('Source file is malformed (missing "industries").');
                return self::FAILURE;
            }
            foreach ($incoming['industries'] as $name => $metrics) {
                $data['industries'][$name] = $metrics;
            }
            if (isset($incoming['source'])) {
                $data['source'] = $incoming['source'];
            }
            $willWrite = true;
            $this->info('Merged ' . count($incoming['industries']) . ' industry rows from source.');
        }

        // Validate every industry row.
        $errors = $this->validate($data['industries']);
        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }
            $this->error('Validation failed — nothing written.');
            return self::FAILURE;
        }

        // Restamp the review date when refreshing (or when explicitly asked).
        if ($willWrite || $this->option('date')) {
            $data['reviewed'] = $this->option('date') ?: date('Y-m-d');
            file_put_contents(
                $path,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
            );
            $this->info("Benchmark dataset written. Reviewed: {$data['reviewed']}.");
        }

        $this->info(sprintf(
            'Validated %d industries. Last reviewed: %s.',
            count($data['industries']),
            $data['reviewed'] ?? 'unknown'
        ));
        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $industries the industry rows to validate
     * @return array<int, string> the validation error messages (empty when valid)
     */
    private function validate(array $industries): array
    {
        $errors = [];
        foreach ($industries as $name => $metrics) {
            if (!is_array($metrics)) {
                $errors[] = "Industry \"$name\" is not an object.";
                continue;
            }
            foreach (self::REQUIRED_METRICS as $key) {
                if (!isset($metrics[$key]) || !is_numeric($metrics[$key]) || $metrics[$key] < 0) {
                    $errors[] = "Industry \"$name\" has a missing or invalid \"$key\".";
                }
            }
        }
        return $errors;
    }
}
