@extends('layouts.dashboard')

@section('title', 'Impostazioni')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header con breadcrumb -->
        <div class="mb-6">
            <nav class="hidden sm:flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-4">
                    <li>
                        <div class="flex">
                            <span class="text-md font-medium text-gray-500">
                                Impostazioni
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:tracking-tight">
                    Impostazioni di Sistema
                </h2>
            </div>
        </div>

        <!-- Content Container -->
        <div class="bg-custom-card shadow-md rounded-xl">
            <div class="px-4 py-5 sm:p-6">
                <!-- Database Management Section -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Gestione Database</h3>
                    <a href="{{ route('settings.adminer') }}" target="_blank"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-md font-medium rounded-xl text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                        <i data-lucide="database" class="w-5 h-5 mr-2"></i>
                        Apri Adminer
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
