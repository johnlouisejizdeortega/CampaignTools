<?php

namespace App\Console\Commands;

use App\Models\LsaDailyCost;
use App\Models\LsaLead;
use App\Models\LsaLeadConversation;
use App\Models\LsaSyncLog;
use App\Services\GoogleAds\LsaClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Pulls Local Services Ads leads, conversations and daily costs from the Google
 * Ads API into the local database. Runs on a schedule (every 15 min); one
 * account failing never aborts the others.
 */
class LsaSyncCommand extends Command
{
    protected $signature = 'lsa:sync {--customer= : Sync only this customer id}';

    protected $description = 'Sync Local Services Ads leads from the Google Ads API into the local DB';

    public function handle(): int
    {
        $customerIds = $this->option('customer')
            ? [preg_replace('/\D/', '', (string) $this->option('customer'))]
            : config('lsa.client_customer_ids', []);

        if (empty($customerIds)) {
            $this->error('No LSA client customer IDs configured. Set LSA_CLIENT_CUSTOMER_IDS in the environment.');

            return self::FAILURE;
        }

        try {
            $client = app(LsaClient::class);
        } catch (Throwable $e) {
            $this->error('Could not build the Google Ads client — check credentials: ' . $e->getMessage());

            return self::FAILURE;
        }

        $windowStart = Carbon::now()->subDays((int) config('lsa.sync_window_days', 35))->toDateString();
        $today = Carbon::now()->toDateString();
        $maxLeads = (int) config('lsa.max_leads_per_account', 5000);
        $hadError = false;

        foreach ($customerIds as $customerId) {
            $log = LsaSyncLog::create([
                'customer_id' => $customerId,
                'started_at' => now(),
                'status' => 'running',
            ]);

            try {
                $count = $this->syncAccount($client, $customerId, $windowStart, $today, $maxLeads);
                $log->update([
                    'finished_at' => now(),
                    'leads_synced' => $count,
                    'status' => 'success',
                ]);
                $this->info("Account {$customerId}: synced {$count} leads.");
            } catch (Throwable $e) {
                $hadError = true;
                $log->update([
                    'finished_at' => now(),
                    'status' => 'error',
                    'error' => substr($e->getMessage(), 0, 2000),
                ]);
                $this->error("Account {$customerId}: {$e->getMessage()}");
            }
        }

        return $hadError ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Syncs a single account. Returns the number of leads upserted.
     */
    private function syncAccount(LsaClient $client, string $customerId, string $windowStart, string $today, int $maxLeads): int
    {
        $now = now();

        // Daily costs first, so we can attach the account currency to leads.
        $costs = $client->fetchDailyCosts($customerId, $windowStart, $today);
        $currency = $costs[0]['currency'] ?? null;
        if ($costs) {
            LsaDailyCost::upsert(
                array_map(fn (array $c) => [
                    'customer_id' => $customerId,
                    'date' => $c['date'],
                    'cost' => $c['cost'],
                    'currency' => $c['currency'],
                    'synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $costs),
                ['customer_id', 'date'],
                ['cost', 'currency', 'synced_at', 'updated_at']
            );
        }

        // Leads.
        $leads = $client->fetchLeads($customerId, $windowStart, $maxLeads);
        if ($leads) {
            LsaLead::upsert(
                array_map(fn (array $l) => [
                    'id' => $l['id'],
                    'customer_id' => $customerId,
                    'lead_type' => $l['lead_type'],
                    'category_id' => $l['category_id'],
                    'service_id' => $l['service_id'],
                    'contact_name' => $l['contact_name'],
                    'contact_phone' => $l['contact_phone'],
                    'contact_email' => $l['contact_email'],
                    'lead_status' => $l['lead_status'],
                    'charged' => $l['lead_charged'],
                    'currency' => $currency,
                    'note' => $l['note'],
                    'created_at_google' => $l['creation_date_time'] ? Carbon::parse($l['creation_date_time']) : null,
                    'synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $leads),
                ['id'],
                ['customer_id', 'lead_type', 'category_id', 'service_id', 'contact_name', 'contact_phone', 'contact_email', 'lead_status', 'charged', 'currency', 'note', 'created_at_google', 'synced_at', 'updated_at']
            );
        }

        // Conversations.
        $conversations = array_values(array_filter(
            $client->fetchConversations($customerId),
            fn (array $c) => !empty($c['id']) && !empty($c['lead_id'])
        ));
        if ($conversations) {
            LsaLeadConversation::upsert(
                array_map(fn (array $c) => [
                    'id' => $c['id'],
                    'lead_id' => $c['lead_id'],
                    'type' => $c['type'],
                    'body' => $c['body'],
                    'occurred_at' => $c['occurred_at'] ? Carbon::parse($c['occurred_at']) : null,
                    'synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $conversations),
                ['id'],
                ['lead_id', 'type', 'body', 'occurred_at', 'synced_at', 'updated_at']
            );
        }

        return count($leads);
    }
}
