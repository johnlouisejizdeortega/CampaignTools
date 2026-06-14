import { ChevronDown } from 'lucide-react';

type Highlight = 'blue' | 'red' | 'none';

const STYLES: Record<Highlight, string> = {
    blue: 'bg-[#1a73e8] text-white border-transparent',
    red: 'bg-[#d93025] text-white border-transparent',
    none: 'bg-card text-foreground',
};

/**
 * A Google Ads "scorecard" — the big metric tiles above the Overview chart.
 * The first two tiles are colour-highlighted (blue clicks, red impressions),
 * matching the console.
 */
export default function Scorecard({
    label,
    value,
    highlight = 'none',
    selectable = true,
}: {
    label: string;
    value: string;
    highlight?: Highlight;
    selectable?: boolean;
}) {
    const onColor = highlight !== 'none';
    return (
        <div className={`flex min-w-0 flex-col gap-2 border-b border-r px-5 py-4 first:border-l ${STYLES[highlight]}`}>
            <div className={`flex items-center gap-1 text-sm ${onColor ? 'text-white/90' : 'text-muted-foreground'}`}>
                <span className="truncate">{label}</span>
                {selectable && <ChevronDown className="h-4 w-4 shrink-0" />}
            </div>
            <div className="text-3xl font-normal tracking-tight">{value}</div>
        </div>
    );
}
