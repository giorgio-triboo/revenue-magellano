<!DOCTYPE html>
<html lang="it" class="h-full bg-custom-platform-background">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/magellano-ico.png') }}">
    <title>{{ config('app.name') }} - @yield('title')</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="h-full font-sans bg-custom-platform">
    <div class="min-h-full flex flex-col">
        <!-- Header -->
        <nav class="bg-transparent">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="/" class="text-xl font-bold text-gray-800">
                                {{ config('app.name') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <div x-data="flashMessages()" class="fixed top-4 right-4 z-50 space-y-4">
            <!-- Success Message -->
            <div x-show="successMessage" x-cloak
                class="bg-custom-card p-4 rounded-xl border border-gray-200 shadow-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i data-lucide="check-circle" class="h-5 w-5 text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800" x-text="successMessage"></p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="successMessage = ''" class="text-green-400 hover:text-green-500">
                            <span class="sr-only">Chiudi</span>
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div x-show="errorMessage" x-cloak
                class="bg-custom-card p-4 rounded-xl border border-gray-200 shadow-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800" x-text="errorMessage"></p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="errorMessage = ''" class="text-red-400 hover:text-red-500">
                            <span class="sr-only">Chiudi</span>
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-grow">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-transparent mt-auto">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Tutti i diritti riservati.
                </p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>