@extends('layout.master_layout')

@section('title', 'Two-Factor Verification')

@section('content')
    <div class="container mx-auto mt-8">
        <div class="max-w-md mx-auto bg-white p-6 rounded-md shadow-md">
            <h1 class="text-2xl font-bold mb-6">Two-Factor Verification</h1>

            <div class="mb-4 text-sm text-gray-600">
                {{ new Illuminate\Support\HtmlString(__("Received an email with a login code? If not, click <a class=\"text-blue-500 hover:underline\" href=\":url\">here</a>.", ['url' => route('verify.resend')])) }}
            </div>

            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('verify.store') }}">
                @csrf

                <div class="mb-6">
                    <label for="two_factor_code" class="block text-gray-700 text-sm font-bold mb-2">Verification Code</label>
                    <input type="text" name="two_factor_code" id="two_factor_code" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required autofocus>
                </div>

                <div class="flex items-center justify-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
