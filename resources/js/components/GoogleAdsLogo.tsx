import type { SVGProps } from 'react';

/**
 * The Google Ads logo mark (the same icon used as the site favicon): a blue and
 * yellow "A" with a green pivot dot. Rendered as inline SVG so it stays crisp at
 * any size. Pass a Tailwind size via className (e.g. "h-7 w-7").
 */
export default function GoogleAdsLogo({ className, ...props }: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 48 48"
            className={className}
            role="img"
            aria-label="Google Ads"
            {...props}
        >
            <line x1="25" y1="9" x2="12" y2="39" stroke="#FBBC04" strokeWidth="11.5" strokeLinecap="round" />
            <line x1="23" y1="9" x2="36" y2="39" stroke="#4285F4" strokeWidth="11.5" strokeLinecap="round" />
            <circle cx="12" cy="39" r="5.75" fill="#34A853" />
        </svg>
    );
}
