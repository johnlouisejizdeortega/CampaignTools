import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { BookOpen, Flame, Gauge, LineChart, ListChecks, Sparkles } from 'lucide-react';
import Tip from '@/components/Tip';
import ScoreGauge from '@/components/ScoreGauge';
import { playbook } from '@/data/playbook';
import { verticalTips } from '@/data/verticalTips';
import type { Benchmark as BenchmarkType, BadgeVariant, Finding, KnowledgeMeta, Recommendation } from '@/types';

const SEVERITY_BADGE: Record<Finding['severity'], BadgeVariant> = {
    critical: 'destructive',
    warning: 'warning',
    info: 'info',
};

const CURRENCY_SYMBOL: Record<string, string> = { USD: '$', GBP: '£', EUR: '€' };

function fmt(value: number | null | undefined, format: string, currency = 'USD') {
    if (value === null || value === undefined) return '—';
    if (format === 'percent') return `${(value * 100).toFixed(2)}%`;
    if (format === 'currency') {
        const symbol = CURRENCY_SYMBOL[currency] ?? `${currency} `;
        return `${symbol}${Number(value).toFixed(2)}`;
    }
    return String(value);
}

function OptimizationScore({ score }: { score: number | null }) {
    if (score === null || score === undefined) return null;
    return (
        <Card className="mb-6">
            <CardContent className="flex items-center gap-6 pt-6">
                <ScoreGauge value={score} />
                <div>
                    <h3 className="flex items-center gap-2 text-lg font-semibold tracking-tight">
                        <Gauge className="h-5 w-5 text-muted-foreground" /> Optimization score
                    </h3>
                    <p className="mt-1 max-w-prose text-sm text-muted-foreground">
                        Google's own estimate of how well this account is set to perform.{' '}
                        <a className="underline-offset-4 hover:underline" target="_blank" rel="noreferrer"
                           href="https://support.google.com/google-ads/answer/9061546">Learn more</a>.
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}

function Benchmark({ benchmark }: { benchmark: BenchmarkType | null }) {
    if (!benchmark) return null;
    return (
        <Card className="mb-6">
            <CardHeader>
                <CardTitle>
                    <LineChart className="h-5 w-5 text-muted-foreground" /> Industry benchmarks
                    <Badge variant="secondary">{benchmark.industry}</Badge>
                </CardTitle>
                <CardDescription>
                    Directional only — aggregated third-party averages, not a guarantee.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Metric</TableHead>
                                <TableHead>Your account</TableHead>
                                <TableHead>Industry avg.</TableHead>
                                <TableHead>vs. benchmark</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {benchmark.metrics.map((m) => {
                                const has = m.account !== null && m.account !== undefined;
                                const better = m.account == null
                                    ? null
                                    : m.betterWhenHigher
                                        ? m.account >= m.benchmark
                                        : m.account <= m.benchmark;
                                return (
                                    <TableRow key={m.label}>
                                        <TableCell className="font-medium">{m.label}</TableCell>
                                        <TableCell>{fmt(m.account, m.format, benchmark.currency)}</TableCell>
                                        <TableCell className="text-muted-foreground">{fmt(m.benchmark, m.format, benchmark.currency)}</TableCell>
                                        <TableCell>
                                            {has ? (
                                                <Badge variant={better ? 'success' : 'warning'}>
                                                    {better ? 'On par / better' : 'Below average'}
                                                </Badge>
                                            ) : '—'}
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>
                {benchmark.source && (
                    <p className="mt-3 text-xs text-muted-foreground">
                        Source:{' '}
                        <a href={benchmark.source.url} target="_blank" rel="noreferrer" className="underline-offset-4 hover:underline">
                            {benchmark.source.label}
                        </a>
                        {benchmark.reviewed && <span> · reviewed {benchmark.reviewed}</span>}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

function FreshnessBar({ meta }: { meta: KnowledgeMeta | null }) {
    if (!meta) return null;
    const parts: string[] = [];
    if (meta.dataWindow) parts.push(`${meta.dataWindow} of account data`);
    if (meta.analyzedAt) parts.push(`analyzed ${meta.analyzedAt}`);
    if (meta.rulesVersion) parts.push(`ruleset v${meta.rulesVersion}`);
    if (meta.benchmarksReviewed) parts.push(`benchmarks reviewed ${meta.benchmarksReviewed}`);
    return (
        <p className="mb-6 text-xs text-muted-foreground">{parts.join(' · ')}</p>
    );
}

interface RecommendationsResultProps {
    customerId: string;
    industry?: string | null;
    recommendations?: Recommendation[];
    findings?: Finding[];
    optimizationScore?: number | null;
    benchmark?: BenchmarkType | null;
    meta?: KnowledgeMeta | null;
    error?: string | null;
}

export default function RecommendationsResult({
    customerId,
    industry = null,
    recommendations = [],
    findings = [],
    optimizationScore = null,
    benchmark = null,
    meta = null,
    error = null,
}: RecommendationsResultProps) {
    const industryTips = industry ? verticalTips[industry] : undefined;
    return (
        <AppLayout title="Optimization suggestions" subtitle={`For account ${customerId}`}>
            <Head title="Optimization suggestions" />
            {!error && <FreshnessBar meta={meta} />}

            {error ? (
                <Alert variant="destructive" className="mb-6">
                    <AlertDescription>
                        <strong>Couldn't analyze the account.</strong> This usually means the server
                        isn't connected to Google Ads yet, or the Customer ID is invalid. You can
                        still use the playbook below.
                        <span className="mt-1 block text-xs text-muted-foreground">
                            Details: {String(error).slice(0, 240)}
                        </span>
                    </AlertDescription>
                </Alert>
            ) : (
                <>
                    <OptimizationScore score={optimizationScore} />

                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>
                                <ListChecks className="h-5 w-5 text-muted-foreground" /> Account analysis
                                <Badge variant="secondary">Rules-based · sourced</Badge>
                                {findings.length > 0 && <Badge variant="warning">{findings.length} issue{findings.length === 1 ? '' : 's'}</Badge>}
                            </CardTitle>
                            <CardDescription>
                                Deterministic checks against your real account data. Every finding cites
                                an official Google source — no AI.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {findings.length === 0 ? (
                                <Alert variant="info">
                                    <AlertDescription>
                                        No rule-based issues detected from the available data. Keep an eye
                                        on the playbook below.
                                    </AlertDescription>
                                </Alert>
                            ) : (
                                findings.map((f, i) => (
                                    <Tip
                                        key={i}
                                        title={f.title}
                                        meta={f.campaign ? `· ${f.campaign}` : null}
                                        badge={{ variant: SEVERITY_BADGE[f.severity] ?? 'info', label: f.severity }}
                                        problem={f.why}
                                        fix={f.fix}
                                        fixLabel="How to fix"
                                        source={f.source}
                                        reviewed={f.reviewed}
                                    />
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Benchmark benchmark={benchmark} />

                    {recommendations.length > 0 && (
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle>
                                    <Sparkles className="h-5 w-5 text-muted-foreground" /> Google recommendations
                                    <Badge variant="success">{recommendations.length} from Google</Badge>
                                </CardTitle>
                                <CardDescription>
                                    Generated by Google Ads' own engine for this account.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {recommendations.map((rec, i) => (
                                    <Tip
                                        key={i}
                                        title={rec.title}
                                        meta={rec.campaignId ? `· campaign ${rec.campaignId}` : null}
                                        badge={{ variant: rec.badge, label: rec.type.replace(/_/g, ' ') }}
                                        problem={rec.why}
                                        fix={rec.fix}
                                        fixLabel="How to fix"
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </>
            )}

            {industryTips && industryTips.length > 0 && (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>
                            <Flame className="h-5 w-5 text-muted-foreground" /> {industry} — tactics for this vertical
                        </CardTitle>
                        <CardDescription>
                            Industry-specific plays on top of the general playbook.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {industryTips.map((tip, i) => (
                            <Tip key={i} {...tip} />
                        ))}
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader>
                    <CardTitle><BookOpen className="h-5 w-5 text-muted-foreground" /> Optimization playbook</CardTitle>
                    <CardDescription>
                        Source-cited best practices — useful for any account.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                    {playbook.map((tip, i) => (
                        <Tip key={i} {...tip} />
                    ))}
                </CardContent>
            </Card>

            <div className="mt-6">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/">← Back to dashboard</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
