import { cn } from '@/lib/utils';

/**
 * A minimalist circular gauge for a 0–1 score (e.g. Optimization Score).
 */
export default function ScoreGauge({ value, size = 96 }: { value: number; size?: number }) {
    const pct = Math.max(0, Math.min(100, Math.round(value * 100)));
    const stroke = 8;
    const radius = (size - stroke) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference * (1 - pct / 100);
    const color = pct >= 85 ? 'text-success' : pct >= 70 ? 'text-warning' : 'text-destructive';

    return (
        <div className="relative" style={{ width: size, height: size }}>
            <svg width={size} height={size} className="-rotate-90">
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    fill="none"
                    strokeWidth={stroke}
                    className="stroke-muted"
                />
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    fill="none"
                    strokeWidth={stroke}
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={offset}
                    className={cn('stroke-current transition-all', color)}
                />
            </svg>
            <div className="absolute inset-0 flex items-center justify-center">
                <span className={cn('text-xl font-semibold tracking-tight', color)}>{pct}%</span>
            </div>
        </div>
    );
}
