<?php

namespace App\Http\Controllers;

use App\Models\LsaAccount;
use App\Models\LsaDailyCost;
use App\Models\LsaLead;
use App\Support\SpreadsheetReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Exports the LSA reports (lead list and agency overview) as PDF, Excel or CSV.
 * Output is intentionally plain and professional — no decorative characters.
 */
class LsaExportController extends Controller
{
    private const MAX_ROWS = 5000;

    /**
     * Lead list export, honouring the same filters as the inbox.
     */
    public function leads(Request $request)
    {
        $validated = $request->validate([
            'format' => ['required', 'in:pdf,xlsx,csv'],
            'customer_id' => ['nullable', 'string', 'max:16'],
            'lead_status' => ['nullable', 'string', 'max:64'],
            'charged' => ['nullable', 'in:0,1,true,false'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $names = LsaAccount::pluck('name', 'customer_id');

        $leads = LsaLead::query()
            ->when($validated['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($validated['lead_status'] ?? null, fn ($q, $v) => $q->where('lead_status', $v))
            ->when(
                array_key_exists('charged', $validated) && $validated['charged'] !== null,
                fn ($q) => $q->where('charged', filter_var($validated['charged'], FILTER_VALIDATE_BOOLEAN))
            )
            ->when($validated['from'] ?? null, fn ($q, $v) => $q->where('created_at_google', '>=', $v))
            ->when($validated['to'] ?? null, fn ($q, $v) => $q->where('created_at_google', '<=', $v))
            ->orderByDesc('created_at_google')
            ->limit(self::MAX_ROWS)
            ->get();

        $columns = [
            ['label' => 'Received', 'key' => 'received', 'type' => 'datetime'],
            ['label' => 'Account', 'key' => 'account', 'type' => 'text'],
            ['label' => 'Contact', 'key' => 'contact', 'type' => 'text'],
            ['label' => 'Phone', 'key' => 'phone', 'type' => 'text'],
            ['label' => 'Email', 'key' => 'email', 'type' => 'text'],
            ['label' => 'Type', 'key' => 'type', 'type' => 'text'],
            ['label' => 'Status', 'key' => 'status', 'type' => 'text'],
            ['label' => 'Charged', 'key' => 'charged', 'type' => 'bool'],
        ];

        $records = $leads->map(fn (LsaLead $l) => [
            'received' => $l->created_at_google,
            'account' => $names[$l->customer_id] ?? $l->customer_id,
            'contact' => $l->contact_name,
            'phone' => $l->contact_phone,
            'email' => $l->contact_email,
            'type' => $this->titleCase($l->lead_type),
            'status' => $this->titleCase($l->lead_status),
            'charged' => $l->charged,
        ])->all();

        $meta = [
            'Generated' => now()->format('d M Y, H:i'),
            'Leads' => (string) count($records),
        ];
        if ($validated['customer_id'] ?? null) {
            $meta['Account'] = $names[$validated['customer_id']] ?? $validated['customer_id'];
        }
        if (($validated['from'] ?? null) || ($validated['to'] ?? null)) {
            $meta['Range'] = trim(($validated['from'] ?? '…') . ' to ' . ($validated['to'] ?? '…'));
        }

        return $this->render($validated['format'], 'Local Services Ads — Lead Report', $meta, $columns, $records, null, 'lsa-leads-' . now()->format('Y-m-d'));
    }

    /**
     * Agency overview export (one row per client).
     */
    public function accounts(Request $request)
    {
        $format = $request->validate(['format' => ['required', 'in:pdf,xlsx,csv']])['format'];

        $leadAgg = LsaLead::query()
            ->selectRaw('customer_id, COUNT(*) as total_leads, SUM(CASE WHEN charged THEN 1 ELSE 0 END) as charged_leads, MAX(created_at_google) as last_lead_at')
            ->groupBy('customer_id')->get()->keyBy('customer_id');
        $costAgg = LsaDailyCost::query()->selectRaw('customer_id, SUM(cost) as spend')->groupBy('customer_id')->get()->keyBy('customer_id');
        $accounts = LsaAccount::all()->keyBy('customer_id');
        $ids = collect(config('lsa.client_customer_ids', []))->merge($leadAgg->keys())->merge($accounts->keys())->unique()->values();

        $records = $ids->map(function (string $id) use ($leadAgg, $costAgg, $accounts) {
            $lead = $leadAgg->get($id);
            $charged = (int) ($lead->charged_leads ?? 0);
            $spend = (float) (($costAgg->get($id))->spend ?? 0);

            return [
                'account' => $accounts->get($id)->name ?? 'Account',
                'customer_id' => $id,
                'leads' => (int) ($lead->total_leads ?? 0),
                'charged' => $charged,
                'spend' => round($spend, 2),
                'cost_per_lead' => $charged > 0 ? round($spend / $charged, 2) : 0.0,
                'last_lead' => $lead->last_lead_at ?? null,
            ];
        })->sortByDesc('leads')->values()->all();

        $columns = [
            ['label' => 'Account', 'key' => 'account', 'type' => 'text'],
            ['label' => 'Customer ID', 'key' => 'customer_id', 'type' => 'text'],
            ['label' => 'Leads', 'key' => 'leads', 'type' => 'int'],
            ['label' => 'Charged', 'key' => 'charged', 'type' => 'int'],
            ['label' => 'Spend', 'key' => 'spend', 'type' => 'currency'],
            ['label' => 'Cost / lead', 'key' => 'cost_per_lead', 'type' => 'currency'],
            ['label' => 'Last lead', 'key' => 'last_lead', 'type' => 'date'],
        ];

        $totals = [
            'Total',
            '',
            array_sum(array_column($records, 'leads')),
            array_sum(array_column($records, 'charged')),
            round(array_sum(array_column($records, 'spend')), 2),
            '',
            '',
        ];

        $meta = [
            'Generated' => now()->format('d M Y, H:i'),
            'Accounts' => (string) count($records),
        ];

        return $this->render($format, 'Local Services Ads — Accounts Overview', $meta, $columns, $records, $totals, 'lsa-accounts-' . now()->format('Y-m-d'));
    }

    /* --------------------------- shared rendering --------------------------- */

    private function render(string $format, string $title, array $meta, array $columns, array $records, ?array $totals, string $filenameBase)
    {
        $headers = array_column($columns, 'label');
        $aligns = array_map(fn ($c) => in_array($c['type'], ['int', 'currency'], true) ? 'right' : 'left', $columns);

        if ($format === 'csv') {
            return $this->csv($headers, $columns, $records, $totals, "{$filenameBase}.csv");
        }

        if ($format === 'xlsx') {
            $numberFormats = [];
            foreach ($columns as $i => $c) {
                if ($c['type'] === 'currency') {
                    $numberFormats[$i] = '"£"#,##0.00';
                } elseif ($c['type'] === 'int') {
                    $numberFormats[$i] = '#,##0';
                }
            }
            $rows = array_map(fn ($rec) => array_map(fn ($c) => $this->raw($rec[$c['key']] ?? null, $c['type']), $columns), $records);
            if ($totals) {
                $rows[] = $totals;
            }

            return SpreadsheetReport::download($title, $meta, $headers, $aligns, $rows, "{$filenameBase}.xlsx", $numberFormats);
        }

        // PDF — pretty display strings.
        $rows = array_map(fn ($rec) => array_map(fn ($c) => $this->display($rec[$c['key']] ?? null, $c['type']), $columns), $records);
        $displayTotals = $totals ? array_map(fn ($v, $i) => is_numeric($v) ? $this->display($v, $columns[$i]['type']) : $v, $totals, array_keys($totals)) : null;

        $pdf = Pdf::loadView('exports.report', [
            'title' => $title,
            'meta' => $meta,
            'headers' => $headers,
            'aligns' => $aligns,
            'rows' => $rows,
            'totals' => $displayTotals,
            'generatedAt' => now()->format('d M Y, H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("{$filenameBase}.pdf");
    }

    private function csv(array $headers, array $columns, array $records, ?array $totals, string $filename)
    {
        return response()->streamDownload(function () use ($headers, $columns, $records, $totals) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($records as $rec) {
                fputcsv($out, array_map(fn ($c) => $this->raw($rec[$c['key']] ?? null, $c['type']), $columns));
            }
            if ($totals) {
                fputcsv($out, $totals);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Raw value for spreadsheet/CSV (numbers stay numeric). */
    private function raw($value, string $type)
    {
        return match ($type) {
            'int' => (int) $value,
            'currency' => round((float) $value, 2),
            'bool' => $value ? 'Yes' : 'No',
            'date' => $value ? Carbon::parse($value)->format('Y-m-d') : '',
            'datetime' => $value ? Carbon::parse($value)->format('Y-m-d H:i') : '',
            default => (string) ($value ?? ''),
        };
    }

    /** Pretty display string for the PDF. */
    private function display($value, string $type): string
    {
        return match ($type) {
            'int' => number_format((int) $value),
            'currency' => '£' . number_format((float) $value, 2),
            'bool' => $value ? 'Yes' : 'No',
            'date' => $value ? Carbon::parse($value)->format('d M Y') : '—',
            'datetime' => $value ? Carbon::parse($value)->format('d M Y, H:i') : '—',
            default => (string) ($value ?: '—'),
        };
    }

    private function titleCase(?string $value): string
    {
        return $value ? ucwords(strtolower(str_replace('_', ' ', $value))) : '';
    }
}
