import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import Tip from '@/components/Tip';
import { playbook } from '@/data/playbook';

describe('Tip', () => {
    it('renders title, problem and fix', () => {
        render(
            <Tip
                title="Conversion tracking is missing"
                problem="You cannot measure results."
                fix="Set up conversion actions."
            />,
        );
        expect(screen.getByText('Conversion tracking is missing')).toBeInTheDocument();
        expect(screen.getByText(/You cannot measure results/)).toBeInTheDocument();
        expect(screen.getByText(/Set up conversion actions/)).toBeInTheDocument();
    });

    it('renders a clickable source citation when provided', () => {
        render(
            <Tip
                title="Budget limited"
                problem="Ads stop early."
                fix="Raise budget."
                source={{ label: 'Google Ads Help', url: 'https://support.google.com/google-ads/answer/2497703' }}
                reviewed="2026-06-13"
            />,
        );
        const link = screen.getByRole('link', { name: /Google Ads Help/ });
        expect(link).toHaveAttribute('href', 'https://support.google.com/google-ads/answer/2497703');
        expect(screen.getByText(/reviewed 2026-06-13/)).toBeInTheDocument();
    });
});

describe('playbook dataset', () => {
    it('every entry is fully sourced and reviewed', () => {
        expect(playbook.length).toBeGreaterThan(0);
        for (const tip of playbook) {
            expect(tip.title).toBeTruthy();
            expect(tip.problem).toBeTruthy();
            expect(tip.fix).toBeTruthy();
            expect(tip.source?.url).toMatch(/^https:\/\//);
            expect(tip.reviewed).toMatch(/^\d{4}-\d{2}-\d{2}$/);
        }
    });
});
