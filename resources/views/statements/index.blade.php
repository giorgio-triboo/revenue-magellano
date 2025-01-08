@extends('layouts.dashboard')

@section('title', 'Consuntivi')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="statements()">
        <div class="md:flex md:items-center md:justify-between mb-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    Consuntivi
                </h2>
            </div>
            <div class="flex justify-between items-center mb-4">
                <a href="{{ route('statements.export', request()->query()) }}"
                    class="inline-flex justify-end items-center px-4 py-2 rounded-xl bg-custom-btn text-white hover:bg-custom-btn">
                    <i data-lucide="download" class="h-5 w-5 mr-2"></i>
                    Scarica Dati
                </a>
            </div>
        </div>

        <div class="mt-8 space-y-8">
            <!-- Filters & Quick Stats -->
            <div class="grid gap-8 md:grid-cols-3">
                <!-- Search & Filters -->
                <div class="md:col-span-2">
                    <div class="bg-custom-card shadow-md rounded-xl p-6">
                        <form method="GET" action="{{ route('statements.index') }}" class="grid gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="search" class="block text-md font-medium text-gray-700">Ricerca</label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="search" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" name="search" id="search"
                                        class="appearance-none block w-full pl-10 pr-12 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        placeholder="Cerca per campagna, publisher o database"
                                        value="{{ request('search') }}">
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        @if (request('search'))
                                            <a href="{{ request()->url() }}" class="pr-2">
                                                <i data-lucide="x-circle"
                                                    class="h-5 w-5 text-gray-400 hover:text-gray-500"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="statement_month" class="block text-md font-medium text-gray-700">Mese
                                    Consuntivo</label>
                                <select name="statement_month" id="statement_month"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-custom-activeItem focus:ring-custom-activeItem sm:text-md">
                                    <option value="">Tutti i mesi</option>
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}"
                                            {{ request('statement_month') == $month ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($month)->locale('it')->translatedFormat('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="revenue_type" class="block text-md font-medium text-gray-700">Tipologia</label>
                                <select name="revenue_type" id="revenue_type"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-custom-activeItem focus:ring-custom-activeItem sm:text-md">
                                    <option value="">Tutte le tipologie</option>
                                    @foreach (['cpl' => 'CPL', 'cpc' => 'CPC', 'cpm' => 'CPM', 'tmk' => 'TMK', 'crg' => 'CRG', 'cpa' => 'CPA', 'sms' => 'SMS'] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ request('revenue_type') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sm:col-span-2 flex justify-end space-x-4">
                                @if (request()->hasAny(['search', 'statement_month', 'revenue_type']))
                                    <a href="{{ route('statements.index') }}"
                                        class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-200 text-gray-700 bg-white hover:bg-gray-50">
                                        <i data-lucide="x" class="h-5 w-5 mr-2"></i>
                                        Rimuovi Filtri
                                    </a>
                                @endif
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 rounded-xl bg-custom-activeItem text-white hover:bg-custom-activeItem/90">
                                    <i data-lucide="filter" class="h-5 w-5 mr-2"></i>
                                    Filtra
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Total Stats -->
                <div class="bg-custom-card shadow-md rounded-xl p-6 h-full">
                    <div class="flex flex-col h-full">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-base font-medium text-gray-900">Totale Consuntivi</h3>
                        </div>

                        <!-- Amount -->
                        <div class="flex-grow flex flex-col items-center justify-center py-6">
                            <div class="flex items-baseline">
                                <span class="text-4xl font-bold text-gray-900">
                                    {{ number_format($totals['fatturazione'], 2, ',', '.') }}
                                </span>
                                <span class="ml-1 text-xl text-gray-500">€</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="">
                            <a href="{{ route('statements.details', request()->query()) }}"
                                class="flex w-full justify-center items-center px-4 py-2.5 rounded-xl bg-custom-activeItem text-white hover:bg-custom-activeItem/90 transition-colors duration-200">
                                <i data-lucide="list" class="h-5 w-5 mr-2"></i>
                                <span class="font-medium">Dettaglio Consuntivi</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Stats -->
            <div class="bg-custom-card shadow-md rounded-xl">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Statistiche Mensili {{ $selectedYear }}</h3>
                        <div class="flex gap-2">
                            @foreach ($availableYears as $year)
                                <a href="{{ route('statements.index', array_merge(request()->query(), ['year' => $year])) }}"
                                    class="px-4 py-2 rounded-xl {{ $year == $selectedYear ? 'bg-custom-activeItem text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                    {{ $year }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @php
                            $currentMonth = now()->month;
                            $displayMonths = collect($monthlyStats)->filter(function($stat) use ($currentMonth) {
                                return $stat['month_number'] < $currentMonth;
                            });
                        @endphp
                        
                        @forelse($displayMonths as $stat)
                            <a href="{{ route('statements.details', array_merge(request()->query(), ['statement_month' => $stat['month_number']])) }}"
                                class="block">
                                <div
                                    class="bg-white p-4 rounded-lg shadow transition-opacity hover:opacity-75 {{ $stat['has_data'] ? '' : 'opacity-50' }}">
                                    <div class="flex flex-col items-center">
                                        <h4 class="text-lg font-medium text-gray-900">{{ $stat['month'] }}</h4>
                                        <span
                                            class="mt-2 text-2xl font-bold {{ $stat['has_data'] ? 'text-custom-activeItem' : 'text-gray-400' }}">
                                            {{ number_format($stat['fatturazione'], 2, ',', '.') }} €
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="col-span-full text-center text-gray-500">Nessun dato disponibile per questo anno
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection