import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Building2, RefreshCw } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import ExportMenu from '@/components/ExportMenu';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { LsaAccountStat } from '@/types';

function money(amount: number, currency: string): string {
    try {
        return new Intl.NumberFormat('en-GB', { style: 'currency', currency: currency || 'GBP', maximumFractionDigits: 2 }).format(amount);
    } catch {
        return `${currency} ${amount.toFixed(2)}`;
    }
}

function when(value: string | null): string {
    if (!value) return '—';
    const d = new Date(value.replace(' ', 'T'));
    return isNaN(d.getTime()) ? value : d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function StatTile({ label, value }: { label: string; value: string }) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div className="mt-1 text-2xl font-normal tracking-tight text-foreground">{value}</div>
            </CardContent>
        </Card>
    );
}

export default function Accounts() {
    const [rows, setRows] = useState<LsaAccountStat[] | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const load = () => {
        setLoading(true);
        setError(null);
        fetch('/api/accounts', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then((r) => { if (!r.ok) throw new Error(`request failed (${r.status})`); return r.json(); })
            .then((d: LsaAccountStat[]) => setRows(d))
            .catch((e) => { setRows([]); setError(e instanceof Error ? e.message : 'could not load accounts'); })
            .finally(() => setLoading(false));
    };
    useEffect(load, []);

    const data = rows ?? [];
    const currency = data.find((r) => r.currency)?.currency ?? 'GBP';
    const totalLeads = data.reduce((s, r) => s + r.total_leads, 0);
    const totalCharged = data.reduce((s, r) => s + r.charged_leads, 0);
    const totalSpend = data.reduce((s, r) => s + r.spend, 0);
    const activeAccounts = data.filter((r) => r.total_leads > 0).length;

    return (
        <AppLayout>
            <Head title="LSA Accounts" />

            <div className="mb-4 flex items-center justify-between">
                <h1 className="flex items-center gap-2 text-[1.75rem] font-normal tracking-tight text-foreground">
                    <Building2 className="h-6 w-6 text-muted-foreground" /> Accounts overview
                </h1>
                <div className="flex items-center gap-2">
                    <ExportMenu baseUrl="/api/accounts/export" params={{}} />
                    <Button variant="outline" size="sm" onClick={load} disabled={loading}>
                        <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} /> Refresh
                    </Button>
                </div>
            </div>

            {error && (
                <div className="mb-4 rounded-md border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive">
                    Couldn't load accounts ({error}). If you just deployed, make sure the migration + a sync have run.
                </div>
            )}

            <div className="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatTile label="Accounts with leads" value={`${activeAccounts} / ${data.length}`} />
                <StatTile label="Total leads" value={String(totalLeads)} />
                <StatTile label="Charged leads" value={String(totalCharged)} />
                <StatTile label="Total spend" value={money(totalSpend, currency)} />
            </div>

            <Card className="overflow-hidden p-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">Account</th>
                                <th className="px-4 py-2 text-right font-medium">Leads</th>
                                <th className="px-4 py-2 text-right font-medium">Charged</th>
                                <th className="px-4 py-2 text-right font-medium">Spend</th>
                                <th className="px-4 py-2 text-right font-medium">Cost / lead</th>
                                <th className="px-4 py-2 font-medium">Last lead</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading && data.length === 0 ? (
                                Array.from({ length: 6 }).map((_, i) => (
                                    <tr key={i} className="border-b">
                                        {Array.from({ length: 6 }).map((__, j) => (
                                            <td key={j} className="px-4 py-3"><div className="h-4 w-20 animate-pulse rounded bg-muted" /></td>
                                        ))}
                                    </tr>
                                ))
                            ) : data.length === 0 ? (
                                <tr><td colSpan={6} className="px-4 py-16 text-center text-muted-foreground">
                                    No accounts yet. They appear after the first <code className="rounded bg-muted px-1">lsa:sync</code>.
                                </td></tr>
                            ) : (
                                data.map((a) => (
                                    <tr
                                        key={a.customer_id}
                                        onClick={() => router.visit(`/leads?customer_id=${a.customer_id}`)}
                                        className="cursor-pointer border-b hover:bg-muted/50"
                                    >
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-foreground">{a.name ?? 'Account'}</div>
                                            <div className="text-xs text-muted-foreground">{a.customer_id}</div>
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">{a.total_leads}</td>
                                        <td className="px-4 py-3 text-right tabular-nums">{a.charged_leads}</td>
                                        <td className="px-4 py-3 text-right tabular-nums">{money(a.spend, a.currency)}</td>
                                        <td className="px-4 py-3 text-right tabular-nums">{a.cost_per_lead ? money(a.cost_per_lead, a.currency) : '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{when(a.last_lead_at)}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>
            <p className="mt-3 text-xs text-muted-foreground">Tip: click a client to open its lead inbox.</p>
        </AppLayout>
    );
}
