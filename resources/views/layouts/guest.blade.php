<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div>
                <a href="/" class="w-20 h-20 fill-current">
                    <svg xmlns="http://www.w3.org/2000/svg" width="400" height="120" viewBox="0 0 400 120" preserveAspectRatio="xMidYMid meet">
                    <defs>
                        <linearGradient id="bissGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#38b6ff;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#0077b6;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <rect width="100%" height="100%" fill="none"/>
                    <text 
                        x="50%" 
                        y="50%" 
                        text-anchor="middle" 
                        dominant-baseline="middle" 
                        font-family="Segoe UI, Helvetica, Arial, sans-serif" 
                        font-size="60" 
                        font-weight="bold" 
                        fill="url(#bissGradient)">
                        Biss
                    </text>
                    </svg>

                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
