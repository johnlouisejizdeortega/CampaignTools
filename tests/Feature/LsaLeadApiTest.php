<?php

namespace Tests\Feature;

use App\Models\LsaAccount;
use App\Models\LsaDailyCost;
use App\Models\LsaLead;
use App\Models\LsaLeadConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the LSA dashboard's JSON API (served entirely from the DB) and the
 * sync command's guard rails. No Google Ads API calls are made.
 */
class LsaLeadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable the team-password gate so the endpoints are reachable in tests.
        config(['app.team_access_password' => '']);
    }

    public function testLeadsIndexIsPaginatedAndFilterable(): void
    {
        LsaLead::create(['id' => '1', 'customer_id' => '1468333005', 'lead_status' => 'NEW', 'charged' => false, 'created_at_google' => '2026-06-10 09:00:00']);
        LsaLead::create(['id' => '2', 'customer_id' => '1468333005', 'lead_status' => 'BOOKED', 'charged' => true, 'created_at_google' => '2026-06-12 09:00:00']);

        $this->getJson('/api/leads')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.id', '2'); // newest first

        $this->getJson('/api/leads?charged=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', '2');

        $this->getJson('/api/leads?lead_status=NEW')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', '1');
    }

    public function testLeadDetailIncludesConversations(): void
    {
        LsaLead::create(['id' => '5', 'customer_id' => '1468333005', 'lead_status' => 'ACTIVE', 'charged' => true]);
        LsaLeadConversation::create(['id' => 'c1', 'lead_id' => '5', 'type' => 'MESSAGE', 'body' => 'Hello', 'occurred_at' => '2026-06-11 10:00:00']);

        $this->getJson('/api/leads/5')
            ->assertOk()
            ->assertJsonPath('id', '5')
            ->assertJsonPath('conversations.0.body', 'Hello');
    }

    public function testStatsAggregatesLeadsAndSpend(): void
    {
        LsaLead::create(['id' => '1', 'customer_id' => '1468333005', 'lead_status' => 'NEW', 'charged' => false, 'currency' => 'GBP', 'created_at_google' => '2026-06-10 09:00:00']);
        LsaLead::create(['id' => '2', 'customer_id' => '1468333005', 'lead_status' => 'BOOKED', 'charged' => true, 'currency' => 'GBP', 'created_at_google' => '2026-06-12 09:00:00']);
        LsaLead::create(['id' => '3', 'customer_id' => '1468333005', 'lead_status' => 'BOOKED', 'charged' => true, 'currency' => 'GBP', 'created_at_google' => '2026-06-13 09:00:00']);
        LsaDailyCost::create(['customer_id' => '1468333005', 'date' => '2026-06-12', 'cost' => 30.00, 'currency' => 'GBP']);
        LsaDailyCost::create(['customer_id' => '1468333005', 'date' => '2026-06-13', 'cost' => 20.00, 'currency' => 'GBP']);

        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('total_leads', 3)
            ->assertJsonPath('charged_leads', 2)
            ->assertJsonPath('total_spend', 50)
            ->assertJsonPath('avg_cost_per_lead', 25)
            ->assertJsonPath('currency', 'GBP');
    }

    public function testAccountsOverviewAggregatesPerClient(): void
    {
        LsaAccount::create(['customer_id' => '5114179445', 'name' => 'Hale Heating', 'currency' => 'GBP']);
        LsaAccount::create(['customer_id' => '2557126397', 'name' => 'Modern Flooring', 'currency' => 'GBP']);
        // Hale: 3 leads (2 charged), £50 spend
        LsaLead::create(['id' => 'a1', 'customer_id' => '5114179445', 'charged' => true, 'created_at_google' => '2026-06-12 09:00:00']);
        LsaLead::create(['id' => 'a2', 'customer_id' => '5114179445', 'charged' => true, 'created_at_google' => '2026-06-13 09:00:00']);
        LsaLead::create(['id' => 'a3', 'customer_id' => '5114179445', 'charged' => false, 'created_at_google' => '2026-06-14 09:00:00']);
        LsaDailyCost::create(['customer_id' => '5114179445', 'date' => '2026-06-12', 'cost' => 50.00, 'currency' => 'GBP']);
        // Modern Flooring: 1 lead
        LsaLead::create(['id' => 'b1', 'customer_id' => '2557126397', 'charged' => false, 'created_at_google' => '2026-06-10 09:00:00']);

        $res = $this->getJson('/api/accounts')->assertOk();
        // Sorted by total_leads desc -> Hale first.
        $res->assertJsonPath('0.customer_id', '5114179445')
            ->assertJsonPath('0.name', 'Hale Heating')
            ->assertJsonPath('0.total_leads', 3)
            ->assertJsonPath('0.charged_leads', 2)
            ->assertJsonPath('0.spend', 50)
            ->assertJsonPath('0.cost_per_lead', 25)
            ->assertJsonPath('1.customer_id', '2557126397')
            ->assertJsonPath('1.total_leads', 1);
    }

    public function testSyncFailsLoudlyWithoutConfiguredAccounts(): void
    {
        config(['lsa.client_customer_ids' => []]);

        $this->artisan('lsa:sync')
            ->expectsOutputToContain('No LSA client customer IDs configured')
            ->assertExitCode(1);
    }
}
