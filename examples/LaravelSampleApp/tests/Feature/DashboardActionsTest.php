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
 * Covers input validation, login throttling, security headers and the health
 * endpoint added for production reliability.
 */
class DashboardActionsTest extends TestCase
{
    public function testHealthEndpointReturnsOk(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function testReportRejectsInvalidCustomerId(): void
    {
        $this->post('/show-report', [
            'customerId' => '123',
            'reportType' => 'campaign',
            'reportRange' => 'YESTERDAY',
            'entriesPerPage' => '20',
        ])->assertSessionHasErrors('customerId');
    }

    public function testPauseRejectsInvalidIds(): void
    {
        $this->post('/pause-campaign', [
            'customerId' => 'abc',
            'campaignId' => '',
        ])->assertSessionHasErrors(['customerId', 'campaignId']);
    }

    public function testLoginIsRateLimited(): void
    {
        config(['app.team_access_password' => 'correct-horse']);

        $response = null;
        for ($i = 0; $i < 6; $i++) {
            $response = $this->from('/login')->post('/login', ['password' => 'wrong']);
        }

        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertStringContainsString('Too many attempts', $errors->first('password'));
    }

    public function testSecurityHeadersArePresent(): void
    {
        $this->get('/health')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
