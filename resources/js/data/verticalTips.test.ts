import { describe, expect, it } from 'vitest';
import { verticalTips } from '@/data/verticalTips';
import { industries } from '@/data/industries';
import benchmarks from '../../knowledge/benchmarks.json';

describe('verticalTips', () => {
    const entries = Object.entries(verticalTips);

    it('is keyed only to known industries', () => {
        for (const [industry] of entries) {
            expect(industries).toContain(industry);
            expect(Object.keys(benchmarks.industries)).toContain(industry);
        }
    });

    it('has complete, well-formed, source-cited tips', () => {
        expect(entries.length).toBeGreaterThan(0);
        for (const [, tips] of entries) {
            expect(tips.length).toBeGreaterThan(0);
            for (const tip of tips) {
                expect(tip.title.trim()).not.toBe('');
                expect(tip.problem?.trim()).not.toBe('');
                expect(tip.fix?.trim()).not.toBe('');
                expect(tip.badge?.label.trim()).not.toBe('');
                // Every tip must cite a real Google source over https.
                expect(tip.source?.label.trim()).not.toBe('');
                expect(tip.source?.url).toMatch(/^https:\/\/(support\.)?google\.com\//);
            }
        }
    });
});
