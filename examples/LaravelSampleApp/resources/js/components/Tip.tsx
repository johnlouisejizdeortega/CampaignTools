import { Badge } from '@/components/ui/badge';
import type { BadgeVariant, Source } from '@/types';

interface TipProps {
    title: string;
    meta?: string | null;
    badge?: { variant: BadgeVariant; label: string };
    problem: string;
    fix: string;
    fixLabel?: string;
    source?: Source;
    reviewed?: string | null;
}

export default function Tip({ title, meta, badge, problem, fix, fixLabel = 'Fix', source, reviewed }: TipProps) {
    return (
        <div className="rounded-md border bg-card p-4">
            <div className="mb-2 flex items-start justify-between gap-3">
                <p className="font-semibold leading-tight">
                    {title}
                    {meta && <span className="font-normal text-muted-foreground"> {meta}</span>}
                </p>
                {badge && <Badge variant={badge.variant}>{badge.label}</Badge>}
            </div>
            <p className="mb-2 text-sm text-muted-foreground">{problem}</p>
            <p className="text-sm">
                <span className="font-medium">{fixLabel}:</span> {fix}
            </p>
            {source && (
                <p className="mt-2 text-xs text-muted-foreground">
                    Source:{' '}
                    <a
                        href={source.url}
                        target="_blank"
                        rel="noreferrer"
                        className="underline-offset-4 hover:underline"
                    >
                        {source.label}
                    </a>
                    {reviewed && <span> · reviewed {reviewed}</span>}
                </p>
            )}
        </div>
    );
}
