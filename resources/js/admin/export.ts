/**
 * Formats a Date object to YYYY-MM-DDTHH:mm for datetime-local inputs.
 * @param date 
 * @returns 
 */
export function formatDateTimeLocal(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

export interface DateRange {
    start: Date;
    end: Date;
}

/**
 * Returns a date range based on the selection type.
 * @param type - 'month', 'quarter', 'year', 'all'
 * @param now - Optional reference date
 * @returns 
 */
export function getDateRange(type: string, now: Date = new Date()): DateRange {
    const end = new Date(now);
    let start: Date;

    switch (type) {
        case 'month':
            start = new Date(now);
            start.setMonth(now.getMonth() - 1);
            break;
        case 'quarter':
            start = new Date(now);
            start.setMonth(now.getMonth() - 3);
            break;
        case 'year':
            start = new Date(now);
            start.setFullYear(now.getFullYear() - 1);
            break;
        case 'all':
            start = new Date(2020, 0, 1, 0, 0, 0); // Jan 1, 2020
            break;
        default:
            start = new Date(now);
    }

    return { start, end };
}

export interface ValidationResult {
    isValid: boolean;
    message?: string;
}

/**
 * Validates the date range.
 * @param start 
 * @param end 
 * @param now - Optional reference date
 * @returns 
 */
export function validateRange(start: Date, end: Date, now: Date = new Date()): ValidationResult {
    if (isNaN(start.getTime()) || isNaN(end.getTime())) {
        return { isValid: false, message: 'Invalid date parameters.' };
    }

    if (start >= end) {
        return { isValid: false, message: 'Start date must be earlier than end date.' };
    }

    if (start > now || end > now) {
        return { isValid: false, message: 'Dates cannot be in the future.' };
    }

    return { isValid: true };
}

/**
 * Initializes the export form.
 */
export function initExportForm(): void {
    const $ = (window as any).$ || (window as any).jQuery;
    if (!$) return;

    $(document).ready(function () {
        const $form = $('form[action*="/api/admin/export/csv"]');
        const $startDate = $('#start_date');
        const $endDate = $('#end_date');

        if (!$form.length) return;

        // Quick Selection Buttons
        $('#btn-month').on('click', () => setRange('month'));
        $('#btn-quarter').on('click', () => setRange('quarter'));
        $('#btn-year').on('click', () => setRange('year'));
        $('#btn-all').on('click', () => setRange('all'));
        
        // Since Last Export button
        $('#btn-since-last-export').on('click', function() {
            const lastExportDate = $(this).data('last-export');
            if (lastExportDate) {
                const start = new Date(lastExportDate);
                const end = new Date();
                $startDate.val(formatDateTimeLocal(start));
                $endDate.val(formatDateTimeLocal(end));
            }
        });

        function setRange(type: string): void {
            const { start, end } = getDateRange(type);
            $startDate.val(formatDateTimeLocal(start));
            $endDate.val(formatDateTimeLocal(end));
        }

        // Initial setup for dates if empty
        const { start: defaultStart, end: defaultEnd } = getDateRange('month');
        if ($startDate.length && !$startDate.val()) {
            $startDate.val(formatDateTimeLocal(defaultStart));
        }
        if ($endDate.length && !$endDate.val()) {
            $endDate.val(formatDateTimeLocal(defaultEnd));
        }

        $form.on('submit', function (e: any) {
            const startStr = $startDate.val() as string;
            const endStr = $endDate.val() as string;

            const start = new Date(startStr);
            const end = new Date(endStr);

            const validation = validateRange(start, end);

            if (!validation.isValid) {
                e.preventDefault();
                if ((window as any).showMessage) {
                    (window as any).showMessage(validation.message, 'error');
                } else {
                    alert(validation.message);
                }
            }
        });
    });
}

// Auto-initialize if running in a browser and jQuery is available
if (typeof window !== 'undefined') {
    initExportForm();
}
