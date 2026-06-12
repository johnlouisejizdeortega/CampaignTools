import { Badge } from '@/components/ui/badge';

export default function Tip({ title, meta, badge, problem, fix, fixLabel = 'Fix' }) {
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
        </div>
    );
}
