import { useState, type FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { BarChart3, CirclePause, Gauge, Plus, Search, ShieldCheck } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import Tip from '@/components/Tip';
import Scorecard from '@/components/Scorecard';
import MetricChart from '@/components/MetricChart';
import { playbook } from '@/data/playbook';
import { industries } from '@/data/industries';
import type { OverviewData } from '@/types';

const CURRENCY_SYMBOL: Record<string, string> = { USD: '$', GBP: '£', EUR: '€', PHP: '₱', AUD: 'A$', CAD: 'C$' };
const compact = new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 });
const sym = (c: string) => CURRENCY_SYMBOL[c] ?? `${c} `;

/* ----------------------------- Overview header ---------------------------- */

function OverviewHeader() {
    return (
        <>
            <div className="mb-4 flex items-center justify-between">
                <h1 className="text-[1.75rem] font-normal tracking-tight text-foreground">Overview</h1>
                <div className="rounded-full border bg-card px-3 py-1.5 text-sm text-muted-foreground">
                    Last 30 days
                </div>
            </div>
            <div className="mb-5 flex items-center gap-6 border-b">
                <span className="-mb-px border-b-[3px] border-primary pb-2 text-sm font-medium text-foreground">Home</span>
            </div>
        </>
    );
}

/* --------------------------- Overview metrics ----------------------------- */

function OptimizationScoreCard({ score }: { score: number | null }) {
    const pct = score === null || score === undefined ? null : Math.round(score * 1000) / 10;
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-base"><Gauge className="h-5 w-5 text-muted-foreground" /> Optimization score</CardTitle>
            </CardHeader>
            <CardContent>
                {pct === null ? (
                    <p className="text-sm text-muted-foreground">Connect an account to see its optimization score.</p>
                ) : (
                    <>
                        <div className="text-4xl font-normal text-primary">{pct}%</div>
                        <div className="mt-3 h-1.5 w-full rounded-full bg-muted">
                            <div className="h-1.5 rounded-full bg-primary" style={{ width: `${pct}%` }} />
                        </div>
                        <p className="mt-3 text-sm text-muted-foreground">
                            Apply Google's recommendations to raise this score.
                        </p>
                    </>
                )}
            </CardContent>
        </Card>
    );
}

function OverviewMetrics({ overview }: { overview: OverviewData }) {
    const t = overview.totals;
    const c = sym(overview.currency);
    return (
        <>
            <Card className="mb-5 overflow-hidden p-0">
                <div className="grid grid-cols-2 border-t md:grid-cols-4">
                    <Scorecard label="Clicks" value={compact.format(t.clicks)} highlight="blue" selectable={false} />
                    <Scorecard label="Impressions" value={compact.format(t.impressions)} highlight="red" selectable={false} />
                    <Scorecard label="Avg. CPC" value={`${c}${t.avgCpc.toFixed(2)}`} selectable={false} />
                    <Scorecard label="Cost" value={`${c}${compact.format(t.cost)}`} selectable={false} />
                </div>
                <div className="p-5">
                    {overview.series.length >= 2 ? (
                        <MetricChart series={overview.series} />
                    ) : (
                        <p className="py-10 text-center text-sm text-muted-foreground">No daily data available for this period.</p>
                    )}
                </div>
            </Card>
            <div className="mb-6 grid gap-5 md:grid-cols-3">
                <OptimizationScoreCard score={overview.optimizationScore} />
                <Card className="md:col-span-2">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base"><BarChart3 className="h-5 w-5 text-muted-foreground" /> Account summary</CardTitle>
                        <CardDescription>Last 30 days · account {overview.customerId}</CardDescription>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                        <div><div className="text-muted-foreground">Clicks</div><div className="text-lg">{compact.format(t.clicks)}</div></div>
                        <div><div className="text-muted-foreground">Impressions</div><div className="text-lg">{compact.format(t.impressions)}</div></div>
                        <div><div className="text-muted-foreground">Conversions</div><div className="text-lg">{compact.format(t.conversions)}</div></div>
                        <div><div className="text-muted-foreground">Cost</div><div className="text-lg">{c}{compact.format(t.cost)}</div></div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function ConnectAccount({ error }: { error?: string | null }) {
    const form = useForm({ customerId: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/', { customerId: form.data.customerId }, { preserveScroll: true });
    };
    return (
        <Card className="mb-6">
            <CardHeader>
                <CardTitle className="text-base"><Search className="h-5 w-5 text-muted-foreground" /> Connect your account</CardTitle>
                <CardDescription>Enter a 10-digit Customer ID (no dashes) to load your live Overview.</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="flex max-w-md gap-2">
                    <Input
                        placeholder="1234567890"
                        value={form.data.customerId}
                        onChange={(e) => form.setData('customerId', e.target.value)}
                        inputMode="numeric"
                    />
                    <Button type="submit">View overview</Button>
                </form>
                {error && <p className="mt-2 text-xs text-destructive">Couldn't load that account: {String(error).slice(0, 160)}</p>}
            </CardContent>
        </Card>
    );
}

/* ------------------------------ Tools (real) ------------------------------ */

function ShowReportForm() {
    const form = useForm({
        customerId: '', reportType: 'campaign', reportRange: 'YESTERDAY',
        entriesPerPage: '20', impressions: true, clicks: true, ctr: true,
    });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => {
            const out: Record<string, string> = {
                customerId: data.customerId, reportType: data.reportType,
                reportRange: data.reportRange, entriesPerPage: data.entriesPerPage,
            };
            if (data.impressions) out.impressions = 'metrics.impressions';
            if (data.clicks) out.clicks = 'metrics.clicks';
            if (data.ctr) out.ctr = 'metrics.ctr';
            return out;
        });
        form.post('/show-report');
    };
    const metric = (key: 'impressions' | 'clicks' | 'ctr', label: string) => (
        <label className="flex items-center gap-2 text-sm">
            <Checkbox checked={form.data[key]} onCheckedChange={(v) => form.setData(key, Boolean(v))} />
            {label}
        </label>
    );
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base"><BarChart3 className="h-5 w-5 text-muted-foreground" /> Show a report</CardTitle>
                <CardDescription>Pull live performance data for an account.</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="reportCustomerId">Customer ID</Label>
                        <Input id="reportCustomerId" placeholder="1234567890" value={form.data.customerId}
                            onChange={(e) => form.setData('customerId', e.target.value)} required />
                        {form.errors.customerId && <p className="text-xs text-destructive">{form.errors.customerId}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label>Report type</Label>
                        <Select value={form.data.reportType} onValueChange={(v) => form.setData('reportType', v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="campaign">campaign</SelectItem>
                                <SelectItem value="customer">customer</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label>Metrics</Label>
                        <div className="flex flex-col gap-2">
                            {metric('impressions', 'Impressions')}
                            {metric('clicks', 'Clicks')}
                            {metric('ctr', 'CTR (click-through rate)')}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>Date range</Label>
                            <Select value={form.data.reportRange} onValueChange={(v) => form.setData('reportRange', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="YESTERDAY">Yesterday</SelectItem>
                                    <SelectItem value="LAST_7_DAYS">Last 7 days</SelectItem>
                                    <SelectItem value="LAST_WEEK_MON_SUN">Last week</SelectItem>
                                    <SelectItem value="LAST_MONTH">Last month</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Rows per page</Label>
                            <Select value={form.data.entriesPerPage} onValueChange={(v) => form.setData('entriesPerPage', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="20">20</SelectItem>
                                    <SelectItem value="50">50</SelectItem>
                                    <SelectItem value="100">100</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <Button type="submit" disabled={form.processing}><Search className="h-4 w-4" /> Show report</Button>
                </form>
            </CardContent>
        </Card>
    );
}

function PauseCampaignForm() {
    const form = useForm({ customerId: '', campaignId: '' });
    const [confirming, setConfirming] = useState(false);
    const askConfirm = (e: FormEvent) => { e.preventDefault(); setConfirming(true); };
    const confirmPause = () => { setConfirming(false); form.post('/pause-campaign'); };
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base"><CirclePause className="h-5 w-5 text-muted-foreground" /> Pause a campaign</CardTitle>
                <CardDescription>Temporarily stop a live campaign from spending.</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={askConfirm} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="pauseCustomerId">Customer ID</Label>
                        <Input id="pauseCustomerId" placeholder="1234567890" value={form.data.customerId}
                            onChange={(e) => form.setData('customerId', e.target.value)} required />
                        {form.errors.customerId && <p className="text-xs text-destructive">{form.errors.customerId}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="campaignId">Campaign ID</Label>
                        <Input id="campaignId" placeholder="1234567890" value={form.data.campaignId}
                            onChange={(e) => form.setData('campaignId', e.target.value)} required />
                        {form.errors.campaignId && <p className="text-xs text-destructive">{form.errors.campaignId}</p>}
                    </div>
                    {confirming ? (
                        <Alert variant="destructive">
                            <AlertDescription>
                                <p className="mb-3">
                                    Pause campaign <strong>{form.data.campaignId}</strong> on account{' '}
                                    <strong>{form.data.customerId}</strong>? It will stop spending immediately.
                                </p>
                                <div className="flex gap-2">
                                    <Button type="button" variant="destructive" size="sm" onClick={confirmPause} disabled={form.processing}>Yes, pause it</Button>
                                    <Button type="button" variant="outline" size="sm" onClick={() => setConfirming(false)}>Cancel</Button>
                                </div>
                            </AlertDescription>
                        </Alert>
                    ) : (
                        <Button type="submit" disabled={form.processing}>Pause campaign</Button>
                    )}
                </form>
            </CardContent>
        </Card>
    );
}

function OptimizationPanel() {
    const form = useForm({ customerId: '', industry: 'none' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({ customerId: data.customerId, industry: data.industry === 'none' ? '' : data.industry }));
        form.post('/recommendations');
    };
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    Optimization suggestions
                    <Badge variant="secondary"><ShieldCheck className="mr-1 h-3 w-3" /> Rules-based · sourced</Badge>
                </CardTitle>
                <CardDescription>
                    A deterministic analysis of your real account data plus Google's own
                    recommendations — every finding cites an official source. No AI.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="w-full space-y-2 sm:max-w-xs">
                        <Label htmlFor="optCustomerId">Customer ID</Label>
                        <Input id="optCustomerId" placeholder="1234567890" value={form.data.customerId}
                            onChange={(e) => form.setData('customerId', e.target.value)} required />
                        {form.errors.customerId && <p className="text-xs text-destructive">{form.errors.customerId}</p>}
                    </div>
                    <div className="w-full space-y-2 sm:max-w-xs">
                        <Label>Industry (optional, for benchmarks)</Label>
                        <Select value={form.data.industry} onValueChange={(v) => form.setData('industry', v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No benchmark</SelectItem>
                                {industries.map((name) => (<SelectItem key={name} value={name}>{name}</SelectItem>))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" disabled={form.processing}>Analyze</Button>
                </form>
                <div>
                    <h3 className="mb-3 text-sm font-semibold">Optimization playbook — common problems &amp; how to fix them</h3>
                    <div className="space-y-3">
                        {playbook.map((tip, i) => (<Tip key={i} {...tip} />))}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

/* -------------------------------- Page ------------------------------------ */

export default function Dashboard({ overview = null }: { overview?: OverviewData | null }) {
    const connected = overview && !overview.error;
    return (
        <AppLayout>
            <Head title="Overview" />
            <OverviewHeader />

            {connected
                ? <OverviewMetrics overview={overview as OverviewData} />
                : <ConnectAccount error={overview?.error} />}

            <div className="mb-3 flex items-center gap-2">
                <Plus className="h-4 w-4 text-muted-foreground" />
                <h2 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Tools</h2>
            </div>
            <div className="grid gap-5 md:grid-cols-2">
                <div id="report" className="scroll-mt-24"><ShowReportForm /></div>
                <div id="campaigns" className="scroll-mt-24"><PauseCampaignForm /></div>
            </div>
            <div className="mt-5 scroll-mt-24" id="optimization"><OptimizationPanel /></div>
        </AppLayout>
    );
}
