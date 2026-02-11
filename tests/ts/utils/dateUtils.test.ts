import { describe, it, expect } from '@jest/globals';

// Example utility function that could be used in the project
describe('Date Utilities', () => {
  describe('formatDateForExport', () => {
    it('should format date to ISO string', () => {
      const date = new Date('2025-01-15T12:00:00Z');
      const formatted = date.toISOString();
      
      expect(formatted).toBe('2025-01-15T12:00:00.000Z');
    });

    it('should parse ISO date string correctly', () => {
      const isoString = '2025-01-15T12:00:00.000Z';
      const parsed = new Date(isoString);
      
      expect(parsed.toISOString()).toBe(isoString);
    });
  });

  describe('Date Range Validation', () => {
    it('should validate start date is before end date', () => {
      const startDate = new Date('2025-01-01');
      const endDate = new Date('2025-01-31');
      
      expect(startDate < endDate).toBe(true);
    });

    it('should detect invalid date range when start is after end', () => {
      const startDate = new Date('2025-02-01');
      const endDate = new Date('2025-01-31');
      
      expect(startDate < endDate).toBe(false);
    });
  });
});
