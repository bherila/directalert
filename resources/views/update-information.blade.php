@extends('layout.master_layout')

@section('title', 'Update Contact Information')

@section('custom_js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Apply phone number mask
        $('#home_phone, #work_phone, #cell_phone').mask('(000) 000-0000');
    });
</script>
@endsection

@section('content')
<div class="container mx-auto p-6">
    <x-h1>Update Contact Information</x-h1>
    <p class="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed mb-6">
        Great! We found your account. Please let us know how you'd like to be contacted.
    </p>

    {{-- Display validation errors --}}
    @if ($errors->any())
        <div class="bg-red-100 dark:bg-red-200 border border-red-400 text-red-700 dark:text-red-900 px-4 py-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ url('/update-information') }}">
        @csrf {{-- CSRF token for security --}}

        <div class="space-y-6">
            {{-- Email Address --}}
            <x-form-row>
                <x-label for="email">Email Address</x-label>
                <div class="w-full sm:w-3/4">
                    <input type="email" name="email" id="email" value="{{ old('email', $account->email ?? '') }}" placeholder="e.g., example@domain.com" class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 focus:outline-none focus:ring focus:ring-blue-300 dark:focus:ring-blue-500">
                </div>
            </x-form-row>

            <x-form-row>
                <label class="w-full sm:w-1/4"></label>
                <div class="w-full sm:w-3/4">
                    <div class="flex items-center">
                        <input type="checkbox" name="optin_emergency_email" id="optin_emergency_email" value="1" {{ old('optin_emergency_email', $account->optin_emergency_email ? 'checked' : '') }} class="mr-2">
                        <label for="optin_emergency_email" class="text-gray-900 dark:text-gray-100">Email me in case of emergency</label>
                    </div>
                </div>
            </x-form-row>

            {{-- Home Phone --}}
            <x-form-row>
                <x-label for="home_phone">Home Phone</x-label>
                <div class="w-full sm:w-3/4">
                    <input type="tel" name="home_phone" id="home_phone" value="{{ old('home_phone', $account->home_phone ?? '') }}" placeholder="e.g., (123) 456-7890" class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 focus:outline-none focus:ring focus:ring-blue-300 dark:focus:ring-blue-500">
                </div>
            </x-form-row>
            <x-form-row>
                <label class="w-full sm:w-1/4"></label>
                <div class="w-full sm:w-3/4">
                    <div class="flex items-center">
                        <input type="checkbox" name="optin_home_call" id="optin_home_call" value="1" {{ old('optin_home_call', $account->optin_home_call ? 'checked' : '') }} class="mr-2">
                        <label for="optin_home_call" class="text-gray-900 dark:text-gray-100">Call me in case of emergency</label>
                    </div>
                </div>
            </x-form-row>

            {{-- Work Phone --}}
            <x-form-row>
                <x-label for="work_phone">Work Phone</x-label>
                <div class="w-full sm:w-3/4">
                    <input type="tel" name="work_phone" id="work_phone" value="{{ old('work_phone', $account->work_phone ?? '') }}" placeholder="e.g., (123) 456-7890" class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 focus:outline-none focus:ring focus:ring-blue-300 dark:focus:ring-blue-500">
                </div>
            </x-form-row>
            <x-form-row>
                <label class="w-full sm:w-1/4"></label>
                <div class="w-full sm:w-3/4">
                    <div class="flex items-center">
                        <input type="checkbox" name="optin_work_call" id="optin_work_call" value="1" {{ old('optin_work_call', $account->optin_work_call ? 'checked' : '') }} class="mr-2">
                        <label for="optin_work_call" class="text-gray-900 dark:text-gray-100">Call me in case of emergency</label>
                    </div>
                </div>
            </x-form-row>

            {{-- Mobile Phone --}}
            <x-form-row>
                <x-label for="cell_phone">Mobile Phone</x-label>
                <div class="w-full sm:w-3/4">
                    <input type="tel" name="cell_phone" id="cell_phone" value="{{ old('cell_phone', $account->cell_phone ?? '') }}" placeholder="e.g., (123) 456-7890" class="w-full border border-gray-300 dark:border-gray-700 rounded px-3 py-2 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 focus:outline-none focus:ring focus:ring-blue-300 dark:focus:ring-blue-500">
                </div>
            </x-form-row>
            <x-form-row>
                <label class="w-full sm:w-1/4"></label>
                <div class="w-full sm:w-3/4">
                    <div class="flex items-center">
                        <input type="checkbox" name="optin_cell_call" id="optin_cell_call" value="1" {{ old('optin_cell_call', $account->optin_cell_call ? 'checked' : '') }} class="mr-2">
                        <label for="optin_cell_call" class="text-gray-900 dark:text-gray-100">Call me in case of emergency</label>
                    </div>
                    <div class="flex items-center mt-2">
                        <input type="checkbox" name="optin_cell_sms" id="optin_cell_sms" value="1" {{ old('optin_cell_sms', $account->optin_cell_sms ? 'checked' : '') }} class="mr-2">
                        <label for="optin_cell_sms" class="text-gray-900 dark:text-gray-100">Text me on my mobile phone in case of emergency (regular SMS rates)</label>
                    </div>
                </div>
            </x-form-row>
        </div>

        <div class="flex items-center justify-between mt-6">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring focus:ring-blue-300 dark:focus:ring-blue-500">
                Submit
            </button>
        </div>
    </form>
</div>
@endsection