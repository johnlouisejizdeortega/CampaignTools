// Industry-specific optimization tactics shown on the optimization page when the
// matching industry is selected. Keyed to the benchmark industry names in
// resources/knowledge/benchmarks.json. Each tip is a well-established Google Ads
// best practice applied to the vertical — no AI-generated content.
import type { PlaybookTip } from '@/types';

export const verticalTips: Record<string, PlaybookTip[]> = {
    'Heating & Boilers (UK)': [
        {
            badge: { variant: 'warning', label: 'Intent' },
            title: 'Separate "emergency repair" from "new installation"',
            problem:
                'Someone searching "boiler not working" needs an engineer today; someone searching "new boiler cost" is comparing quotes. One ad group for both wastes budget and lowers relevance.',
            fix: 'Build distinct campaigns/ad groups: an urgent "repair/emergency" set (call-focused, broad hours) and a "new boiler/replacement" set (quote- and finance-focused).',
            source: { label: 'Google Ads Help — Structure your account', url: 'https://support.google.com/google-ads/answer/6372655' },
            reviewed: '2026-06-14',
        },
        {
            badge: { variant: 'warning', label: 'Quick win' },
            title: 'Add call and location assets, target your service area',
            problem:
                'Boiler work is local and often urgent, but ads without a tap-to-call button or local targeting lose high-intent customers.',
            fix: 'Add call assets (and call-only ads for emergency campaigns), restrict location targeting to the postcodes you actually cover, and use ad scheduling for the hours your team can answer.',
            source: { label: 'Google Ads Help — About call assets', url: 'https://support.google.com/google-ads/answer/2453991' },
            reviewed: '2026-06-14',
        },
        {
            badge: { variant: 'info', label: 'Trust' },
            title: 'Put Gas Safe & accreditations in the ad',
            problem:
                'Boiler buyers worry about cowboys. Generic ads that don\'t signal trust convert worse on a high-value purchase.',
            fix: 'Use callout assets and headlines for "Gas Safe registered", manufacturer-accredited installer, guarantees, and reviews (e.g. Which? Trusted Trader).',
            source: { label: 'Google Ads Help — About assets', url: 'https://support.google.com/google-ads/answer/7332837' },
            reviewed: '2026-06-14',
        },
        {
            badge: { variant: 'info', label: 'Conversion' },
            title: 'Lead with fixed-price quotes and finance',
            problem:
                'A new boiler is a big-ticket purchase; ads and landing pages that bury price or finance lose people who would have booked a survey.',
            fix: 'Promote a free fixed-price quote and spread-the-cost / 0% finance options in ad copy, and send clicks to a fast quote form rather than the homepage.',
            source: { label: 'Google Ads Help — About landing page experience', url: 'https://support.google.com/google-ads/answer/6167130' },
            reviewed: '2026-06-14',
        },
        {
            badge: { variant: 'destructive', label: 'Wasting spend' },
            title: 'Block DIY, parts and jobs searches',
            problem:
                'Searches like "boiler pressure too low fix", "boiler parts", or "boiler engineer jobs" click your ads but never become customers.',
            fix: 'Add negatives for DIY/how-to, spares/parts, recruitment ("jobs", "vacancy", "salary"), and rentals; review the Search Terms report weekly during peak season.',
            source: { label: 'Google Ads Help — About negative keywords', url: 'https://support.google.com/google-ads/answer/2453972' },
            reviewed: '2026-06-14',
        },
        {
            badge: { variant: 'info', label: 'Seasonality' },
            title: 'Shift budget to the cold months',
            problem:
                'Boiler demand and breakdowns spike in autumn/winter; a flat year-round budget under-serves your busiest, highest-converting weeks.',
            fix: 'Raise budgets from roughly September to February and during cold snaps, especially on the emergency-repair campaigns, then ease back in summer.',
            source: { label: 'Google Ads Help — About campaign budgets', url: 'https://support.google.com/google-ads/answer/2375420' },
            reviewed: '2026-06-14',
        },
    ],
};
