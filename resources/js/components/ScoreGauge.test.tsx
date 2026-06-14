import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import ScoreGauge from '@/components/ScoreGauge';

describe('ScoreGauge', () => {
    it('renders the rounded percentage', () => {
        render(<ScoreGauge value={0.724} />);
        expect(screen.getByText('72%')).toBeInTheDocument();
    });

    it('clamps out-of-range values', () => {
        const { rerender } = render(<ScoreGauge value={1.5} />);
        expect(screen.getByText('100%')).toBeInTheDocument();
        rerender(<ScoreGauge value={-0.2} />);
        expect(screen.getByText('0%')).toBeInTheDocument();
    });
});
