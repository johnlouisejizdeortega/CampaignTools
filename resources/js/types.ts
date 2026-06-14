import type { BadgeProps } from '@/components/ui/badge';

export type BadgeVariant = BadgeProps['variant'];

export interface Source {
    label: string;
    url: string;
}

export interface PlaybookTip {
    badge: { variant: BadgeVariant; label: string };
    title: string;
    problem: string;
    fix: string;
    source?: Source;
    reviewed?: string;
}

export interface Finding {
    id: string;
    title: string;
    severity: 'critical' | 'warning' | 'info';
    why: string;
    fix: string;
    source: Source;
    reviewed: string | null;
    campaign: string | null;
    observed?: unknown;
}

export interface Recommendation {
    type: string;
    title: string;
    why: string;
    fix: string;
    badge: BadgeVariant;
    campaignId: string | null;
}

export interface BenchmarkMetric {
    label: string;
    account: number | null;
    benchmark: number;
    format: 'percent' | 'currency';
    betterWhenHigher: boolean;
}

export interface Benchmark {
    industry: string;
    currency: string;
    source: Source | null;
    reviewed: string | null;
    note?: string | null;
    metrics: BenchmarkMetric[];
}

export interface KnowledgeMeta {
    analyzedAt?: string;
    dataWindow?: string;
    rulesVersion?: string | null;
    benchmarksReviewed?: string | null;
}

export interface Flash {
    error?: string | null;
    success?: string | null;
}

export interface SharedProps {
    appName: string;
    flash: Flash;
    [key: string]: unknown;
}
