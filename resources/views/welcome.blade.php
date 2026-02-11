@extends('layout.master_layout')

@section('content')
    <x-h1>Validation Information</x-h1>
    <x-p>
        The following information is required for confirmation. We'll check that this information matches our records and if it does, we'll proceed with the information update.
    </x-p>

    <form method="POST" action="{{ url('/verify') }}">
        @csrf {{-- CSRF token for security --}}

        <div class="mb-4">
            <label for="account_number" class="block text-gray-500 dark:text-gray-400 text-sm font-bold mb-2">Account number:</label>
            <input type="text" name="account_number" id="account_number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-6">
            <label for="last_name" class="block text-gray-500 dark:text-gray-400 text-sm font-bold mb-2">Last name on account:</label>
            <input type="text" name="last_name" id="last_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Verify
            </button>
        </div>
    </form>
@endsection