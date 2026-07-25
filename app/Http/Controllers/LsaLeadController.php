<?php

namespace App\Http\Controllers;

use App\Models\LsaAccount;
use App\Models\LsaDailyCost;
use App\Models\LsaLead;
use App\Services\GoogleAds\LsaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Fast JSON API for the LSA lead dashboard. Every read is served from the local
 * database — these endpoints never call Google, so pages load instantly. Only
 * the scheduled `lsa:sync` job talks to the Google Ads API.
 */
class LsaLeadController extends Controller
{
    /**
     * Paginated, filterable lead list (newest first).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'string', 'max:16'],
            'lead_status' => ['nullable', 'string', 'max:64'],
            'charged' => ['nullable', 'in:0,1,true,false'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = LsaLead::query()
            ->when($validated['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($validated['lead_status'] ?? null, fn ($q, $v) => $q->where('lead_status', $v))
            ->when(
                array_key_exists('charged', $validated) && $validated['charged'] !== null,
                fn ($q) => $q->where('charged', filter_var($validated['charged'], FILTER_VALIDATE_BOOLEAN))
            )
            ->when($validated['from'] ?? null, fn ($q, $v) => $q->where('created_at_google', '>=', $v))
            ->when($validated['to'] ?? null, fn ($q, $v) => $q->where('created_at_google', '<=', $v))
            ->orderByDesc('created_at_google');

        return response()->json($query->paginate($validated['per_page'] ?? 25));
    }

    /**
     * Agency overview: one aggregate row per LSA account (leads, charged, spend,
     * cost-per-lead, last lead), for every configured or synced account.
     */
    public function accounts(): JsonResponse
    {
        $leadAgg = LsaLead::query()
            ->selectRaw('customer_id, COUNT(*) as total_leads, SUM(charged) as charged_leads, MAX(created_at_google) as last_lead_at')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $costAgg = LsaDailyCost::query()
            ->selectRaw('customer_id, SUM(cost) as spend')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $accounts = LsaAccount::all()->keyBy('customer_id');

        $ids = collect(config('lsa.client_customer_ids', []))
            ->merge($leadAgg->keys())
            ->merge($accounts->keys())
            ->unique()
            ->values();

        $rows = $ids->map(function (string $id) use ($leadAgg, $costAgg, $accounts) {
            $lead = $leadAgg->get($id);
            $account = $accounts->get($id);
            $charged = (int) ($lead->charged_leads ?? 0);
            $spend = (float) (($costAgg->get($id))->spend ?? 0);

            return [
                'customer_id' => $id,
                'name' => $account->name ?? null,
                'currency' => $account->currency ?? 'GBP',
                'total_leads' => (int) ($lead->total_leads ?? 0),
                'charged_leads' => $charged,
                'spend' => round($spend, 2),
                'cost_per_lead' => $charged > 0 ? round($spend / $charged, 2) : 0.0,
                'last_lead_at' => $lead->last_lead_at ?? null,
            ];
        })->sortByDesc('total_leads')->values();

        return response()->json($rows);
    }

    /**
     * A single lead with its full conversation thread.
     */
    public function show(string $id): JsonResponse
    {
        $lead = LsaLead::with('conversations')->findOrFail($id);

        return response()->json($lead);
    }

    /**
     * Aggregate stats over an optional date range.
     */
    public function stats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'string', 'max:16'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $base = LsaLead::query()
            ->when($validated['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($validated['from'] ?? null, fn ($q, $v) => $q->where('created_at_google', '>=', $v))
            ->when($validated['to'] ?? null, fn ($q, $v) => $q->where('created_at_google', '<=', $v));

        $totalLeads = (clone $base)->count();
        $chargedLeads = (clone $base)->where('charged', true)->count();
        $byStatus = (clone $base)
            ->selectRaw('lead_status, COUNT(*) as count')
            ->groupBy('lead_status')
            ->pluck('count', 'lead_status');
        $currency = (clone $base)->whereNotNull('currency')->value('currency') ?? 'GBP';

        // Spend comes from the LSA campaign's daily cost, not per-lead amounts.
        $spendQuery = LsaDailyCost::query()
            ->when($validated['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($validated['from'] ?? null, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($validated['to'] ?? null, fn ($q, $v) => $q->whereDate('date', '<=', $v));
        $totalSpend = (float) $spendQuery->sum('cost');

        return response()->json([
            'total_leads' => $totalLeads,
            'charged_leads' => $chargedLeads,
            'total_spend' => round($totalSpend, 2),
            'avg_cost_per_lead' => $chargedLeads > 0 ? round($totalSpend / $chargedLeads, 2) : 0.0,
            'by_status' => $byStatus,
            'currency' => $currency,
        ]);
    }

    /**
     * Submit lead feedback/rating to Google — the only write path allowed by the
     * LSA API. Proxies ProvideLeadFeedback via the client.
     */
    public function feedback(Request $request, string $id, LsaClient $client): JsonResponse
    {
        $validated = $request->validate([
            'survey_answer' => ['required', 'in:VERY_SATISFIED,SATISFIED,NEUTRAL,DISSATISFIED,VERY_DISSATISFIED'],
            'reason' => ['nullable', 'in:SPAM,DUPLICATE,GEO_MISMATCH,JOB_TYPE_MISMATCH,NOT_READY_TO_BOOK,SOLICITATION,OTHER_DISSATISFIED_REASON'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $lead = LsaLead::findOrFail($id);

        try {
            $client->provideLeadFeedback(
                $lead->customer_id,
                $lead->id,
                $validated['survey_answer'],
                $validated['reason'] ?? null,
                $validated['comment'] ?? null,
            );

            $lead->update([
                'feedback_submitted' => true,
                'feedback_reason' => $validated['reason'] ?? $validated['survey_answer'],
            ]);

            return response()->json(['status' => 'ok']);
        } catch (Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 502);
        }
    }
}
