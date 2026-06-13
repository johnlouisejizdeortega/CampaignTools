import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import Tip from '@/components/Tip';
import { playbook } from '@/data/playbook';
import { industries } from '@/data/industries';

function ShowReportForm() {
    const form = useForm({
        customerId: '',
        reportType: 'campaign',
        reportRange: 'YESTERDAY',
        entriesPerPage: '20',
        impressions: true,
        clicks: true,
        ctr: true,
    });

    const submit = (e) => {
        e.preventDefault();
        form
            .transform((data) => {
                const out = {
                    customerId: data.customerId,
                    reportType: data.reportType,
                    reportRange: data.reportRange,
                    entriesPerPage: data.entriesPerPage,
                };
                if (data.impressions) out.impressions = 'metrics.impressions';
                if (data.clicks) out.clicks = 'metrics.clicks';
                if (data.ctr) out.ctr = 'metrics.ctr';
                return out;
            })
            .post('/show-report');
    };

    const metric = (key, label) => (
        <label className="flex items-center gap-2 text-sm">
            <Checkbox
                checked={form.data[key]}
                onCheckedChange={(v) => form.setData(key, Boolean(v))}
            />
            {label}
        </label>
    );

    return (
        <Card>
            <CardHeader>
                <CardTitle>Show a report</CardTitle>
                <CardDescription>Pull live performance data for an account.</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="reportCustomerId">Customer ID</Label>
                        <Input
                            id="reportCustomerId"
                            placeholder="1234567890"
                            value={form.data.customerId}
                            onChange={(e) => form.setData('customerId', e.target.value)}
                            required
                        />
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
                    <Button type="submit" disabled={form.processing}>Show report</Button>
                </form>
            </CardContent>
        </Card>
    );
}

function PauseCampaignForm() {
    const form = useForm({ customerId: '', campaignId: '' });
    const submit = (e) => {
        e.preventDefault();
        form.post('/pause-campaign');
    };
    return (
        <Card>
            <CardHeader>
                <CardTitle>Pause a campaign</CardTitle>
                <CardDescription>Temporarily stop a live campaign from spending.</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="pauseCustomerId">Customer ID</Label>
                        <Input
                            id="pauseCustomerId"
                            placeholder="1234567890"
                            value={form.data.customerId}
                            onChange={(e) => form.setData('customerId', e.target.value)}
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="campaignId">Campaign ID</Label>
                        <Input
                            id="campaignId"
                            placeholder="1234567890"
                            value={form.data.campaignId}
                            onChange={(e) => form.setData('campaignId', e.target.value)}
                            required
                        />
                    </div>
                    <Button type="submit" disabled={form.processing}>Pause campaign</Button>
                </form>
            </CardContent>
        </Card>
    );
}

function OptimizationPanel() {
    const form = useForm({ customerId: '', industry: 'none' });
    const submit = (e) => {
        e.preventDefault();
        form
            .transform((data) => ({
                customerId: data.customerId,
                industry: data.industry === 'none' ? '' : data.industry,
            }))
            .post('/recommendations');
    };
    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    Optimization suggestions
                    <Badge variant="secondary">
                        <ShieldCheck className="mr-1 h-3 w-3" /> Rules-based · sourced
                    </Badge>
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
                        <Input
                            id="optCustomerId"
                            placeholder="1234567890"
                            value={form.data.customerId}
                            onChange={(e) => form.setData('customerId', e.target.value)}
                            required
                        />
                    </div>
                    <div className="w-full space-y-2 sm:max-w-xs">
                        <Label>Industry (optional, for benchmarks)</Label>
                        <Select value={form.data.industry} onValueChange={(v) => form.setData('industry', v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No benchmark</SelectItem>
                                {industries.map((name) => (
                                    <SelectItem key={name} value={name}>{name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" disabled={form.processing}>Analyze</Button>
                </form>

                <div>
                    <h3 className="mb-3 text-sm font-semibold">
                        Optimization playbook — common problems &amp; how to fix them
                    </h3>
                    <div className="space-y-3">
                        {playbook.map((tip, i) => (
                            <Tip key={i} {...tip} />
                        ))}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard() {
    return (
        <AppLayout
            title="Dashboard"
            subtitle="Manage campaigns, pull reports, and get optimization suggestions."
        >
            <Head title="Dashboard" />
            <div className="space-y-5">
                <Alert variant="muted">
                    <AlertDescription>
                        Enter a 10-digit <strong>Customer ID</strong> (no dashes) in any panel below.
                        Connecting an account requires a configured{' '}
                        <code className="rounded bg-background px-1 py-0.5">google_ads_php.ini</code>{' '}
                        on the server — see <code className="rounded bg-background px-1 py-0.5">TEAM_SETUP.md</code>.
                    </AlertDescription>
                </Alert>
                <div className="grid gap-5 md:grid-cols-2">
                    <ShowReportForm />
                    <PauseCampaignForm />
                </div>
                <OptimizationPanel />
            </div>
        </AppLayout>
    );
}
