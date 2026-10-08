import { describe, expect, it } from 'vitest';
import { apiError, nextDays, splitByPartOfDay, validateBooking } from './booking';

describe('nextDays', () => {
    it('returns consecutive ISO dates across a month boundary', () => {
        expect(nextDays(new Date('2026-10-30T22:00:00Z'), 3)).toEqual(['2026-10-30', '2026-10-31', '2026-11-01']);
    });
});

describe('splitByPartOfDay', () => {
    it('groups slots by local hour and drops empty groups', () => {
        const groups = splitByPartOfDay([
            { local_time: '10:00' },
            { local_time: '17:00' },
            { local_time: '18:00' },
        ]);
        expect(groups.map(([k, list]) => [k, list.length])).toEqual([
            ['Morning', 1],
            ['Evening', 2],
        ]);
    });
});

describe('validateBooking', () => {
    it('accepts a valid name and email', () => {
        expect(validateBooking({ name: 'Sofia', email: 'sofia@example.com' })).toEqual({});
    });
    it('flags a short name and a broken email', () => {
        expect(Object.keys(validateBooking({ name: 'S', email: 'sofia@' }))).toEqual(['name', 'email']);
    });
});

describe('apiError', () => {
    it('explains a conflict and flattens validation errors', () => {
        expect(apiError(409, {})).toContain('taken');
        expect(apiError(422, { errors: { subject: ['Wrong subject.'] } })).toBe('Wrong subject.');
    });
});
