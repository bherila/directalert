@extends('layout.master_layout')

@section('title', 'Import Results')

@section('content')
<div class="w-full mx-auto sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <x-admin-tab-bar />
            
            <h1 class="text-2xl font-bold mb-6 dark:text-white">Import Results</h1>

            @if(count($imported) > 0)
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4 text-green-600 dark:text-green-400">Successfully Imported Records ({{ count($imported) }})</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Account Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Account Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Zip Code</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach($imported as $record)
                            <tr>
                                <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $record['account_name'] }}</td>
                                <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $record['account_number'] }}</td>
                                <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $record['zip_code'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if(count($duplicates) > 0)
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4 text-yellow-600 dark:text-yellow-400">Skipped Duplicate Records ({{ count($duplicates) }})</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Account Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Account Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Zip Code</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach($duplicates as $record)
                            <tr>
                                <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $record['account_name'] }}</td>
                                <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $record['account_number'] }}</td>
                                <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $record['zip_code'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if(count($failed) > 0)
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4 text-red-600 dark:text-red-400">Failed to Import ({{ count($failed) }})</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Row</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Error</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach($failed as $record)
                            <tr>
                                <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $record['row'] }}</td>
                                <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $record['data'] }}</td>
                                <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $record['error'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="mt-6">
                <a href="{{ url('/admin/import') }}" class="inline-block bg-gray-200 text-gray-700 py-2 px-4 rounded hover:bg-gray-300 transition-colors dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    ← Back to Import
                </a>
            </div>
        </div>
    </div>
</div>
@endsection