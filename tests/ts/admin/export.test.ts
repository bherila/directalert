import { describe, it, expect } from '@jest/globals';
import { formatDateTimeLocal, getDateRange, validateRange } from '@/admin/export';

describe('Export Utility Functions', () => {
    describe('formatDateTimeLocal', () => {
        it('should format date correctly', () => {
            const date = new Date(2025, 4, 2, 8, 23); // May 2, 2025, 08:23
            expect(formatDateTimeLocal(date)).toBe('2025-05-02T08:23');
        });

        it('should pad single digits with zeros', () => {
            const date = new Date(2025, 0, 1, 1, 1); // Jan 1, 2025, 01:01
            expect(formatDateTimeLocal(date)).toBe('2025-01-01T01:01');
        });
    });

    describe('getDateRange', () => {
        const mockNow = new Date(2026, 1, 11, 10, 0, 0); // Feb 11, 2026

        it('should return correct range for month', () => {
            const { start, end } = getDateRange('month', mockNow);
            expect(end).toEqual(mockNow);
            expect(start.getMonth()).toBe(0); // January
            expect(start.getFullYear()).toBe(2026);
        });

        it('should return correct range for year', () => {
            const { start, end } = getDateRange('year', mockNow);
            expect(end).toEqual(mockNow);
            expect(start.getFullYear()).toBe(2025);
            expect(start.getMonth()).toBe(1); // February
        });

        it('should return correct range for "all"', () => {
            const { start, end } = getDateRange('all', mockNow);
            expect(end).toEqual(mockNow);
            expect(start.getFullYear()).toBe(2020);
            expect(start.getMonth()).toBe(0);
            expect(start.getDate()).toBe(1);
        });
    });

    describe('validateRange', () => {
        const mockNow = new Date(2026, 1, 11, 10, 0, 0);

        it('should return valid for correct range', () => {
            const start = new Date(2026, 1, 1);
            const end = new Date(2026, 1, 10);
            expect(validateRange(start, end, mockNow)).toEqual({ isValid: true });
        });

        it('should return invalid if start > end', () => {
            const start = new Date(2026, 1, 10);
            const end = new Date(2026, 1, 1);
            expect(validateRange(start, end, mockNow)).toEqual({
                isValid: false,
                message: 'Start date must be earlier than end date.'
            });
        });

        it('should return invalid if dates are in the future', () => {
            const start = new Date(2026, 1, 1);
            const end = new Date(2026, 1, 12); // Future relative to mockNow
            expect(validateRange(start, end, mockNow)).toEqual({
                isValid: false,
                message: 'Dates cannot be in the future.'
            });
        });

        it('should return invalid for invalid dates', () => {
            expect(validateRange(new Date('invalid'), new Date(), mockNow)).toEqual({
                isValid: false,
                message: 'Invalid date parameters.'
            });
        });
    });
});
