<!DOCTYPE html>
<html lang="it" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title')</title>

    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="h-full font-sans bg-custom-sidebar">
    
    <div x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 flex w-80 flex-col">
            <div class="flex min-h-0 flex-1 flex-col bg-custom-sidebar overflow-hidden">
                <!-- Logo -->
                <div class="flex h-16 flex-shrink-0 items-center px-4 border-b border-custom-textMain/30">
                    <span class="text-2xl font-bold text-text_main">{{ config('app.name') }}</span>
                </div>

                <!-- Sidebar content -->
                <div class="flex flex-1 flex-col">
                    @include('components.sidebar-content')
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="pl-80 min-h-screen">
            <main class="h-screen p-3">
                <div class="rounded-xl bg-custom-platform p-6 lg:p-8 min-h-[calc(100vh-24px)]"> <!-- 24px = 3 * padding di 8px (p-3) -->
                    <div class="mx-auto">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>

</html>