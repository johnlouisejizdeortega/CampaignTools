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

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Writes an append-only audit trail of destructive actions (e.g. pausing a
 * campaign) to storage/logs/audit.log, so there is always a record of what was
 * changed, from where, and when. With shared-password access there is no
 * per-user identity, so the session fingerprint and IP are recorded instead.
 */
class AuditLogger
{
    /**
     * @param Request $request the HTTP request that triggered the action
     * @param string $action a short action key, e.g. "campaign.pause"
     * @param array<string, mixed> $context action-specific details
     */
    public static function record(Request $request, string $action, array $context = []): void
    {
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
        ])->info($action, array_merge($context, [
            'ip' => $request->ip(),
            'session' => substr((string) $request->session()->getId(), 0, 12),
            'at' => now()->toIso8601String(),
        ]));
    }
}
