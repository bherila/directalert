<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

        <x-head />

        <title>@yield('title', 'Direct Alert Verification')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/brands.min.css" integrity="sha512-58P9Hy7II0YeXLv+iFiLCv1rtLW47xmiRpC1oFafeKNShp8V5bKV/ciVtYqbk2YfxXQMt58DjNfkXFOn62xE+g==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Styles -->
        @vite('resources/css/app.css')
    </head>
    <body class="antialiased">
        <x-header />
            {{-- @if (Route::has('login'))
                <div class="sm:fixed sm:top-0 sm:right-0 p-6 text-right z-10">
                    @auth
                        <a href="{{ url('/home') }}" class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Home</a>
                    @else
                        <a href="{{ route('login') }}" class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Log in</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Register</a>
                        @endif
                    @endauth
                </div>
            @endif --}}

            <div class="container mx-auto p-6 lg:p-8 flex flex-col space-y-4">
                <div class="flex justify-center">
                    <img src="https://www.middlesexwater.com/wp-content/uploads/2018/06/MWC_Blue.png" alt="Middlesex Water Company Logo" class="h-16">
                </div>
                <div class="mt-16 justify-center mx-auto">
                    <div class="container mx-auto p-2">
                        <div class="bg-white p-8 rounded-lg shadow-lg dark:bg-gray-800">
                            @yield('content')
                        </div>
                    </div>
                </div>
                <div class="flex justify-center mt-16 px-0 mx-auto">
                    <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ date('Y') }} Middlesex Water Company. All rights reserved.
                    </div>
                </div>
            </div>

            <x-footer />
        @yield('custom_js')
    </body>
</html>
