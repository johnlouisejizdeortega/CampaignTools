import { useCallback, useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import { Inbox, Phone, Mail, X, RefreshCw, PhoneCall, MessageSquare, CalendarCheck, Flag } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import ExportMenu from '@/components/ExportMenu';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import type { LsaLead, LsaLeadDetail, LsaStats, Paginated } from '@/types';

const STATUSES = ['NEW', 'ACTIVE', 'BOOKED', 'DECLINED', 'EXPIRED', 'DISABLED', 'CONSUMER_DECLINED', 'WIPED_OUT'];

const STATUS_CLASS: Record<string, string> = {
    NEW: 'bg-[#e8f0fe] text-[#1a73e8]',
    ACTIVE: 'bg-[#e6f4ea] text-[#1e8e3e]',
    BOOKED: 'bg-[#e6f4ea] text-[#1e8e3e]',
    DECLINED: 'bg-[#fce8e6] text-[#d93025]',
    CONSUMER_DECLINED: 'bg-[#fce8e6] text-[#d93025]',
    EXPIRED: 'bg-muted text-muted-foreground',
    DISABLED: 'bg-muted text-muted-foreground',
    WIPED_OUT: 'bg-muted text-muted-foreground',
};

const TYPE_ICON: Record<string, typeof Phone> = {
    PHONE_CALL: PhoneCall,
    MESSAGE: MessageSquare,
    BOOKING: CalendarCheck,
};

// Reasons Google reviews when reporting a bad lead (for a possible credit).
const REPORT_REASONS: { value: string; label: string }[] = [
    { value: 'SPAM', label: 'Spam' },
    { value: 'DUPLICATE', label: 'Duplicate lead' },
    { value: 'GEO_MISMATCH', label: 'Wrong location' },
    { value: 'JOB_TYPE_MISMATCH', label: 'Wrong job type' },
    { value: 'NOT_READY_TO_BOOK', label: 'Not ready to book' },
    { value: 'SOLICITATION', label: 'Solicitation / sales call' },
    { value: 'OTHER_DISSATISFIED_REASON', label: 'Other' },
];

function csrfToken(): string {
    const m = typeof document !== 'undefined' ? document.cookie.match(/XSRF-TOKEN=([^;]+)/) : null;
    return m ? decodeURIComponent(m[1]) : '';
}

interface Filters {
    customer_id: string;
    lead_status: string;
    charged: string;
    from: string;
    to: string;
}

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
    return isNaN(d.getTime()) ? value : d.toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function StatusBadge({ status }: { status: string | null }) {
    if (!status) return <span className="text-muted-foreground">—</span>;
    return <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_CLASS[status] ?? 'bg-muted text-muted-foreground'}`}>{status.replace(/_/g, ' ').toLowerCase()}</span>;
}

/* -------------------------------- Stats ---------------------------------- */

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

function StatsHeader({ stats, loading }: { stats: LsaStats | null; loading: boolean }) {
    if (loading && !stats) {
        return (
            <div className="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
                {Array.from({ length: 4 }).map((_, i) => (
                    <Card key={i}><CardContent className="p-4"><div className="h-4 w-20 animate-pulse rounded bg-muted" /><div className="mt-2 h-7 w-16 animate-pulse rounded bg-muted" /></CardContent></Card>
                ))}
            </div>
        );
    }
    const c = stats?.currency ?? 'GBP';
    return (
        <div className="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <StatTile label="Total leads" value={String(stats?.total_leads ?? 0)} />
            <StatTile label="Charged leads" value={String(stats?.charged_leads ?? 0)} />
            <StatTile label="Total spend" value={money(stats?.total_spend ?? 0, c)} />
            <StatTile label="Avg. cost / lead" value={money(stats?.avg_cost_per_lead ?? 0, c)} />
        </div>
    );
}

/* ------------------------------- Drawer ---------------------------------- */

function LeadDrawer({ id, onClose }: { id: string; onClose: () => void }) {
    const [lead, setLead] = useState<LsaLeadDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [reason, setReason] = useState('SPAM');
    const [comment, setComment] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [feedbackError, setFeedbackError] = useState<string | null>(null);

    const sendFeedback = (surveyAnswer: string, withReason: boolean) => {
        setSubmitting(true);
        setFeedbackError(null);
        fetch(`/api/leads/${id}/feedback`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': csrfToken() },
            body: JSON.stringify(withReason ? { survey_answer: surveyAnswer, reason, comment } : { survey_answer: surveyAnswer }),
        })
            .then(async (r) => {
                const d = await r.json().catch(() => ({}));
                if (!r.ok || d.status === 'error') throw new Error(d.message || `request failed (${r.status})`);
                setLead((l) => (l ? { ...l, feedback_submitted: true, feedback_reason: withReason ? reason : surveyAnswer } : l));
            })
            .catch((e) => setFeedbackError(e instanceof Error ? e.message : 'could not submit'))
            .finally(() => setSubmitting(false));
    };

    useEffect(() => {
        let active = true;
        setLoading(true);
        fetch(`/api/leads/${id}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((d) => { if (active) { setLead(d); setLoading(false); } })
            .catch(() => { if (active) setLoading(false); });
        return () => { active = false; };
    }, [id]);

    return (
        <div className="fixed inset-0 z-40">
            <div className="absolute inset-0 bg-foreground/40" onClick={onClose} />
            <aside className="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-card shadow-xl">
                <div className="flex items-center justify-between border-b px-5 py-4">
                    <h2 className="text-base font-medium">Lead details</h2>
                    <button onClick={onClose} className="rounded-full p-1.5 text-muted-foreground hover:bg-muted" aria-label="Close"><X className="h-5 w-5" /></button>
                </div>
                <div className="flex-1 overflow-y-auto p-5">
                    {loading ? (
                        <div className="space-y-3">{Array.from({ length: 4 }).map((_, i) => <div key={i} className="h-4 w-full animate-pulse rounded bg-muted" />)}</div>
                    ) : !lead ? (
                        <p className="text-sm text-muted-foreground">Could not load this lead.</p>
                    ) : (
                        <>
                            <div className="flex items-center gap-2">
                                <span className="text-lg font-medium text-foreground">{lead.contact_name ?? 'Unknown contact'}</span>
                                <StatusBadge status={lead.lead_status} />
                            </div>
                            <div className="mt-3 space-y-2 text-sm">
                                {lead.contact_phone && <a href={`tel:${lead.contact_phone}`} className="flex items-center gap-2 text-primary"><Phone className="h-4 w-4" /> {lead.contact_phone}</a>}
                                {lead.contact_email && <a href={`mailto:${lead.contact_email}`} className="flex items-center gap-2 text-primary"><Mail className="h-4 w-4" /> {lead.contact_email}</a>}
                                <div className="text-muted-foreground">Type: {lead.lead_type ?? '—'} · Received {when(lead.created_at_google)}</div>
                                <div className="text-muted-foreground">Charged: {lead.charged ? 'Yes' : 'No'}{lead.category_id ? ` · ${lead.category_id}` : ''}</div>
                                {lead.note && <div className="rounded-md bg-muted p-3 text-foreground">{lead.note}</div>}
                            </div>

                            {/* Lead quality / report to Google */}
                            <div className="mt-6 rounded-md border p-3">
                                {lead.feedback_submitted ? (
                                    <p className="flex items-center gap-2 text-sm text-[#1e8e3e]">
                                        <Flag className="h-4 w-4" /> Feedback submitted to Google{lead.feedback_reason ? ` · ${lead.feedback_reason.replace(/_/g, ' ').toLowerCase()}` : ''}
                                    </p>
                                ) : (
                                    <>
                                        <h3 className="mb-2 text-sm font-semibold">Report a bad lead</h3>
                                        <p className="mb-3 text-xs text-muted-foreground">Flag spam, duplicates or mismatches — Google reviews reported leads for a possible credit.</p>
                                        <div className="flex flex-col gap-2">
                                            <Select value={reason} onValueChange={setReason}>
                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    {REPORT_REASONS.map((r) => <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>)}
                                                </SelectContent>
                                            </Select>
                                            {reason === 'OTHER_DISSATISFIED_REASON' && (
                                                <Input placeholder="Add a note (optional)" value={comment} onChange={(e) => setComment(e.target.value)} />
                                            )}
                                            <div className="flex gap-2">
                                                <Button variant="destructive" size="sm" disabled={submitting} onClick={() => sendFeedback('VERY_DISSATISFIED', true)}>
                                                    <Flag className="h-4 w-4" /> Report lead
                                                </Button>
                                                <Button variant="outline" size="sm" disabled={submitting} onClick={() => sendFeedback('VERY_SATISFIED', false)}>
                                                    Mark as good
                                                </Button>
                                            </div>
                                            {feedbackError && <p className="text-xs text-destructive">Couldn't submit: {feedbackError}</p>}
                                        </div>
                                    </>
                                )}
                            </div>

                            <h3 className="mb-2 mt-6 text-sm font-semibold">Conversation</h3>
                            {lead.conversations.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No messages or calls recorded.</p>
                            ) : (
                                <ul className="space-y-3">
                                    {lead.conversations.map((c) => (
                                        <li key={c.id} className="rounded-md border p-3 text-sm">
                                            <div className="mb-1 flex items-center justify-between text-xs text-muted-foreground">
                                                <span>{c.type?.replace(/_/g, ' ').toLowerCase() ?? 'message'}</span>
                                                <span>{when(c.occurred_at)}</span>
                                            </div>
                                            <div className="text-foreground">{c.body ?? '—'}</div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </>
                    )}
                </div>
            </aside>
        </div>
    );
}

/* -------------------------------- Page ----------------------------------- */

export default function Leads() {
    const initialCustomer = typeof window !== 'undefined'
        ? (new URLSearchParams(window.location.search).get('customer_id') ?? '')
        : '';
    const [filters, setFilters] = useState<Filters>({ customer_id: initialCustomer, lead_status: 'all', charged: 'all', from: '', to: '' });
    const [page, setPage] = useState(1);
    const [leads, setLeads] = useState<Paginated<LsaLead> | null>(null);
    const [stats, setStats] = useState<LsaStats | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selected, setSelected] = useState<string | null>(null);

    const queryString = useCallback((extra: Record<string, string> = {}) => {
        const p = new URLSearchParams();
        if (filters.customer_id) p.set('customer_id', filters.customer_id);
        if (filters.lead_status !== 'all') p.set('lead_status', filters.lead_status);
        if (filters.charged !== 'all') p.set('charged', filters.charged);
        if (filters.from) p.set('from', filters.from);
        if (filters.to) p.set('to', filters.to);
        Object.entries(extra).forEach(([k, v]) => p.set(k, v));
        return p.toString();
    }, [filters]);

    const load = useCallback(() => {
        setLoading(true);
        setError(null);
        const getJson = async (url: string) => {
            const r = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error(`request failed (${r.status})`);
            return r.json();
        };
        Promise.all([
            getJson(`/api/leads?${queryString({ page: String(page), per_page: '25' })}`),
            getJson(`/api/stats?${queryString()}`),
        ])
            .then(([l, s]) => { setLeads(l); setStats(s); })
            .catch((e) => {
                setLeads({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 25 });
                setStats(null);
                setError(e instanceof Error ? e.message : 'could not load leads');
            })
            .finally(() => setLoading(false));
    }, [queryString, page]);

    useEffect(() => { load(); }, [load]);

    const setFilter = (patch: Partial<Filters>) => { setPage(1); setFilters((f) => ({ ...f, ...patch })); };
    const rows = leads?.data ?? [];

    const exportParams: Record<string, string> = {};
    if (filters.customer_id) exportParams.customer_id = filters.customer_id;
    if (filters.lead_status !== 'all') exportParams.lead_status = filters.lead_status;
    if (filters.charged !== 'all') exportParams.charged = filters.charged;
    if (filters.from) exportParams.from = filters.from;
    if (filters.to) exportParams.to = filters.to;

    return (
        <AppLayout>
            <Head title="LSA Leads" />

            <div className="mb-4 flex items-center justify-between">
                <h1 className="flex items-center gap-2 text-[1.75rem] font-normal tracking-tight text-foreground">
                    <Inbox className="h-6 w-6 text-muted-foreground" /> Local Services leads
                </h1>
                <div className="flex items-center gap-2">
                    <ExportMenu baseUrl="/api/leads/export" params={exportParams} />
                    <Button variant="outline" size="sm" onClick={load} disabled={loading}>
                        <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} /> Refresh
                    </Button>
                </div>
            </div>

            {error && (
                <div className="mb-4 rounded-md border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive">
                    Couldn't load leads ({error}). If you just deployed, make sure the database migration has run
                    (<code className="rounded bg-background px-1">php artisan migrate</code>) and the sync has run at least once.
                </div>
            )}

            <StatsHeader stats={stats} loading={loading} />

            {/* Filters */}
            <div className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div className="space-y-1">
                    <Label className="text-xs">Account</Label>
                    <Input placeholder="All accounts" value={filters.customer_id} inputMode="numeric"
                        onChange={(e) => setFilter({ customer_id: e.target.value.replace(/\D/g, '') })} />
                </div>
                <div className="space-y-1">
                    <Label className="text-xs">Status</Label>
                    <Select value={filters.lead_status} onValueChange={(v) => setFilter({ lead_status: v })}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {STATUSES.map((s) => <SelectItem key={s} value={s}>{s.replace(/_/g, ' ').toLowerCase()}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>
                <div className="space-y-1">
                    <Label className="text-xs">Charged</Label>
                    <Select value={filters.charged} onValueChange={(v) => setFilter({ charged: v })}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem value="1">Charged</SelectItem>
                            <SelectItem value="0">Unpaid</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="space-y-1">
                    <Label className="text-xs">From</Label>
                    <Input type="date" value={filters.from} onChange={(e) => setFilter({ from: e.target.value })} />
                </div>
                <div className="space-y-1">
                    <Label className="text-xs">To</Label>
                    <Input type="date" value={filters.to} onChange={(e) => setFilter({ to: e.target.value })} />
                </div>
            </div>

            {/* Table */}
            <Card className="overflow-hidden p-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">Contact</th>
                                <th className="px-4 py-2 font-medium">Type</th>
                                <th className="px-4 py-2 font-medium">Status</th>
                                <th className="px-4 py-2 font-medium">Charged</th>
                                <th className="px-4 py-2 font-medium">Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading && rows.length === 0 ? (
                                Array.from({ length: 6 }).map((_, i) => (
                                    <tr key={i} className="border-b">
                                        {Array.from({ length: 5 }).map((__, j) => (
                                            <td key={j} className="px-4 py-3"><div className="h-4 w-24 animate-pulse rounded bg-muted" /></td>
                                        ))}
                                    </tr>
                                ))
                            ) : rows.length === 0 ? (
                                <tr><td colSpan={5} className="px-4 py-16 text-center text-muted-foreground">
                                    No leads yet. They appear here after the next <code className="rounded bg-muted px-1">lsa:sync</code> once an LSA account is configured.
                                </td></tr>
                            ) : (
                                rows.map((lead) => {
                                    const TypeIcon = TYPE_ICON[lead.lead_type ?? ''] ?? MessageSquare;
                                    return (
                                        <tr key={lead.id} onClick={() => setSelected(lead.id)}
                                            className="cursor-pointer border-b hover:bg-muted/50">
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-foreground">{lead.contact_name ?? 'Unknown'}</div>
                                                <div className="text-xs text-muted-foreground">{lead.contact_phone ?? lead.contact_email ?? '—'}</div>
                                            </td>
                                            <td className="px-4 py-3"><span className="inline-flex items-center gap-1.5 text-muted-foreground"><TypeIcon className="h-4 w-4" /> {(lead.lead_type ?? '—').replace(/_/g, ' ').toLowerCase()}</span></td>
                                            <td className="px-4 py-3"><StatusBadge status={lead.lead_status} /></td>
                                            <td className="px-4 py-3">{lead.charged ? <Badge variant="secondary">Charged</Badge> : <span className="text-muted-foreground">—</span>}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{when(lead.created_at_google)}</td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
                {leads && leads.total > 0 && (
                    <div className="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground">
                        <span>{leads.total} lead{leads.total === 1 ? '' : 's'}</span>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Previous</Button>
                            <span>Page {leads.current_page} of {leads.last_page}</span>
                            <Button variant="outline" size="sm" disabled={page >= leads.last_page} onClick={() => setPage((p) => p + 1)}>Next</Button>
                        </div>
                    </div>
                )}
            </Card>

            {selected && <LeadDrawer id={selected} onClose={() => setSelected(null)} />}
        </AppLayout>
    );
}
