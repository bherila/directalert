@extends('layout.master_layout')

@section('title', 'Admin Export')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <x-admin-tab-bar />
            
            <h1 class="text-2xl font-bold mb-6 dark:text-white">Direct Alert Data Export</h1>

            <form method="POST" action="{{ url('/api/admin/export/csv') }}">
                @csrf
                <div class="form-group mb-4">
                    <label for="start_date" class="block font-medium mb-2 dark:text-gray-200">Start Date and Time:</label>
                    <input type="datetime-local" id="start_date" name="start" required 
                           class="w-full p-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
                </div>

                <div class="form-group mb-4">
                    <label for="end_date" class="block font-medium mb-2 dark:text-gray-200">End Date and Time:</label>
                    <input type="datetime-local" id="end_date" name="end" required
                           class="w-full p-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
                </div>

                <div class="form-group mb-4">
                    <label for="date_format" class="block font-medium mb-2 dark:text-gray-200">Date Format:</label>
                    <select id="date_format" name="date_format" 
                            class="w-full p-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        <option value="default">Default</option>
                        <option value="excel">Excel compatible</option>
                        <option value="iso_with_seconds">ISO 8601 format with seconds (2025-05-02T08:23:00)</option>
                        <option value="iso_without_seconds">ISO 8601 format without seconds</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors dark:bg-blue-700 dark:hover:bg-blue-800">
                    Export CSV
                </button>
            </form>

            <div id="message" class="mt-4 p-4 rounded hidden"></div>
        </div>
    </div>
</div>
@endsection

@section('custom_js')
<script>
    // Function to format date for datetime-local input (YYYY-MM-DDTHH:mm)
    function formatDateTimeLocal(date) {
        const year = date.getFullYear();
        const month = ('0' + (date.getMonth() + 1)).slice(-2);
        const day = ('0' + date.getDate()).slice(-2);
        const hours = ('0' + date.getHours()).slice(-2);
        const minutes = ('0' + date.getMinutes()).slice(-2);
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    $(document).ready(function() {
        const now = new Date();

        // Set end date to current date and time
        $('#end_date').val(formatDateTimeLocal(now));

        // Calculate the first day of the prior month
        let priorYear = now.getFullYear();
        let priorMonthIndex = now.getMonth() - 1;
        if (priorMonthIndex < 0) { // Handle January case
            priorMonthIndex = 11; // December
            priorYear -= 1;
        }
        const priorMonth = new Date(priorYear, priorMonthIndex, 1);
        $('#start_date').val(formatDateTimeLocal(priorMonth));
    });

    function showMessage(message, type) {
        const $messageDiv = $('#message');
        $messageDiv.text(message);
        
        // Reset classes first
        $messageDiv.removeClass().addClass('mt-4 p-4 rounded');
        
        // Add appropriate classes based on message type
        if (type === 'success') {
            $messageDiv.addClass('bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200');
        } else {
            $messageDiv.addClass('bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200');
        }
        
        $messageDiv.show();
    }
</script>
@endsection