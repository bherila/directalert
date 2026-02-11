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
                        <label for="start_date" class="block font-medium mb-2 dark:text-gray-200">Start Date and
                            Time:</label>
                        <input type="datetime-local" id="start_date" name="start" required
                            class="w-full p-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>

                    <div class="form-group mb-4">
                        <label for="end_date" class="block font-medium mb-2 dark:text-gray-200">End Date and Time:</label>
                        <input type="datetime-local" id="end_date" name="end" required
                            class="w-full p-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>

                    <div class="mb-6">
                        <label class="block font-medium mb-2 dark:text-gray-200">Quick Selection:</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" id="btn-month"
                                class="bg-gray-200 dark:bg-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Month</button>
                            <button type="button" id="btn-quarter"
                                class="bg-gray-200 dark:bg-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Quarter</button>
                            <button type="button" id="btn-year"
                                class="bg-gray-200 dark:bg-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Year</button>
                            <button type="button" id="btn-all"
                                class="bg-gray-200 dark:bg-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">All</button>
                            @if($lastExportDate)
                            <button type="button" id="btn-since-last-export" data-last-export="{{ $lastExportDate }}"
                                class="bg-blue-200 dark:bg-blue-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-blue-300 dark:hover:bg-blue-600 transition-colors">Since Last Export</button>
                            @endif
                        </div>
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

                    <button type="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors dark:bg-blue-700 dark:hover:bg-blue-800">
                        Export CSV
                    </button>
                </form>

                <div id="message" class="mt-4 p-4 rounded hidden"></div>

                @if(count($exportHistory) > 0)
                <div class="mt-8">
                    <h2 class="text-xl font-bold mb-4 dark:text-white">Your Export History</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Records</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Error</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                @foreach($exportHistory as $export)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ $export->created_at->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($export->was_successful)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                Success
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                Failed
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ $export->records_affected }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                        {{ $export->error_message ?? '-' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    @vite(['resources/js/admin/export.ts'])
    <script type="module">
        // Globally expose showMessage so it can be called from the modular JS
        window.showMessage = function (message, type) {
            const $messageDiv = $('#message');
            $messageDiv.text(message);
            $messageDiv.removeClass().addClass('mt-4 p-4 rounded');
            if (type === 'success') {
                $messageDiv.addClass('bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200');
            } else {
                $messageDiv.addClass('bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200');
            }
            $messageDiv.show();
        };
    </script>
@endsection