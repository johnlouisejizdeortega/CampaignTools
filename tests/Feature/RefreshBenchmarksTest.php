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

use Tests\TestCase;

/**
 * Exercises the benchmarks:refresh command. The default (validate-only) run does
 * not write to disk, so these tests never mutate the shipped dataset.
 */
class RefreshBenchmarksTest extends TestCase
{
    public function testValidateOnlyRunSucceeds(): void
    {
        $this->artisan('benchmarks:refresh')
            ->expectsOutputToContain('Validated')
            ->assertExitCode(0);
    }

    public function testInvalidDateIsRejectedAndWritesNothing(): void
    {
        $original = file_get_contents(resource_path('knowledge/benchmarks.json'));

        $this->artisan('benchmarks:refresh --date=garbage')->assertExitCode(1);

        $this->assertSame($original, file_get_contents(resource_path('knowledge/benchmarks.json')));
    }

    public function testInvalidSourceFailsAndWritesNothing(): void
    {
        $original = file_get_contents(resource_path('knowledge/benchmarks.json'));

        $bad = tempnam(sys_get_temp_dir(), 'bench') . '.json';
        file_put_contents($bad, json_encode(['industries' => ['Bad' => ['avgCtr' => 'not-a-number']]]));

        $this->artisan("benchmarks:refresh --source={$bad}")
            ->assertExitCode(1);

        // The shipped dataset must be untouched after a failed run.
        $this->assertSame($original, file_get_contents(resource_path('knowledge/benchmarks.json')));

        @unlink($bad);
    }
}
