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

export interface OverviewSeriesPoint {
    date: string;
    clicks: number;
    impressions: number;
    cost: number;
}

export interface OverviewData {
    customerId: string;
    currency: string;
    totals: {
        clicks: number;
        impressions: number;
        cost: number;
        avgCpc: number;
        conversions: number;
    };
    optimizationScore: number | null;
    series: OverviewSeriesPoint[];
    error?: string | null;
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

export interface LsaLead {
    id: string;
    customer_id: string;
    lead_type: string | null;
    category_id: string | null;
    service_id: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    lead_status: string | null;
    charged: boolean;
    currency: string | null;
    note: string | null;
    created_at_google: string | null;
}

export interface LsaLeadConversation {
    id: string;
    lead_id: string;
    type: string | null;
    body: string | null;
    occurred_at: string | null;
}

export interface LsaLeadDetail extends LsaLead {
    conversations: LsaLeadConversation[];
}

export interface LsaStats {
    total_leads: number;
    charged_leads: number;
    total_spend: number;
    avg_cost_per_lead: number;
    by_status: Record<string, number>;
    currency: string;
}

export interface Paginated<T> {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}
