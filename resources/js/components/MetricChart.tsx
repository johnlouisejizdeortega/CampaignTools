import type { OverviewSeriesPoint } from '@/types';

const BLUE = '#1a73e8';
const RED = '#d93025';

/**
 * Lightweight dual-line chart (Clicks vs Impressions over time), hand-rolled in
 * SVG so it needs no charting dependency. Each series is scaled to its own axis,
 * like the Google Ads Overview chart.
 */
export default function MetricChart({ series }: { series: OverviewSeriesPoint[] }) {
    if (!series || series.length < 2) return null;

    const W = 880;
    const H = 260;
    const padX = 6;
    const padTop = 18;
    const padBottom = 18;
    const n = series.length;

    const x = (i: number) => padX + (i / (n - 1)) * (W - 2 * padX);
    const maxClicks = Math.max(...series.map((p) => p.clicks), 1);
    const maxImpr = Math.max(...series.map((p) => p.impressions), 1);
    const yC = (v: number) => H - padBottom - (v / maxClicks) * (H - padTop - padBottom);
    const yI = (v: number) => H - padBottom - (v / maxImpr) * (H - padTop - padBottom);

    const path = (key: 'clicks' | 'impressions', y: (v: number) => number) =>
        series.map((p, i) => `${i === 0 ? 'M' : 'L'} ${x(i).toFixed(1)} ${y(p[key]).toFixed(1)}`).join(' ');

    const gridY = [0.25, 0.5, 0.75].map((f) => padTop + f * (H - padTop - padBottom));
    const first = series[0].date;
    const last = series[n - 1].date;

    return (
        <div>
            <div className="mb-3 flex items-center gap-5 text-xs text-muted-foreground">
                <span className="flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-full" style={{ background: BLUE }} /> Clicks</span>
                <span className="flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-full" style={{ background: RED }} /> Impressions</span>
            </div>
            <svg viewBox={`0 0 ${W} ${H}`} className="h-auto w-full" role="img" aria-label="Clicks and impressions over time">
                {gridY.map((gy, i) => (
                    <line key={i} x1={padX} y1={gy} x2={W - padX} y2={gy} stroke="hsl(var(--border))" strokeWidth={1} />
                ))}
                <line x1={padX} y1={H - padBottom} x2={W - padX} y2={H - padBottom} stroke="hsl(var(--border))" strokeWidth={1} />
                <path d={path('impressions', yI)} fill="none" stroke={RED} strokeWidth={2.5} strokeLinecap="round" strokeLinejoin="round" />
                <path d={path('clicks', yC)} fill="none" stroke={BLUE} strokeWidth={2.5} strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            <div className="mt-1 flex justify-between text-xs text-muted-foreground">
                <span>{first}</span>
                <span>{last}</span>
            </div>
        </div>
    );
}
