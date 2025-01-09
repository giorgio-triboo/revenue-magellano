@extends('layouts.dashboard')

@section('title', 'Dettaglio Consuntivi')

@section('content')
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8" x-data="statementsIndex()">
        <!-- Header con breadcrumb -->
        <div class="mb-6">
            <nav class="sm:hidden" aria-label="Back">
                <a href="{{ route('statements.index') }}"
                    class="flex items-center text-md font-medium text-gray-500 hover:text-gray-700">
                    <i data-lucide="chevron-left" class="flex-shrink-0 -ml-1 mr-1 h-5 w-5 text-gray-400"></i>
                    Torna alla lista
                </a>
            </nav>
            <nav class="hidden sm:flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-4">
                    <li>
                        <div class="flex">
                            <a href="{{ route('statements.index') }}"
                                class="text-md font-medium text-gray-500 hover:text-gray-700">
                                Consuntivi
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i data-lucide="chevron-right" class="flex-shrink-0 h-5 w-5 text-gray-400"></i>
                            <span class="ml-4 text-md font-medium text-gray-500">
                                Dettaglio Consuntivi
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Header -->
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <div class="flex items-center space-x-4">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:tracking-tight">
                        Dettaglio Consuntivi
                    </h2>
                </div>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button @click="exportData" :disabled="loading"
                    class="inline-flex justify-end items-center px-4 py-2 rounded-xl bg-custom-btn text-white hover:bg-custom-btn">
                    <i x-show="!loading" data-lucide="download" class="h-5 w-5 mr-2"></i>
                    <i x-show="loading" data-lucide="loader-2" class="h-5 w-5 mr-2 animate-spin"></i>
                    Esporta Consuntivi
                </button>
            </div>
        </div>

        <!-- Filters & Quick Stats -->
        <div class="mt-8 space-y-8">
            <div class="grid gap-8 md:grid-cols-3">
                <!-- Search & Filters -->
                <div class="md:col-span-2">
                    <div class="bg-custom-card shadow-md rounded-xl p-6">
                        <form method="GET" action="{{ route('statements.details') }}" class="grid gap-6">
                            <!-- Ricerca full-width -->
                            <div class="sm:col-span-2">
                                <label for="search" class="block text-md font-medium text-gray-700">Ricerca</label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="search" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" name="search" id="search"
                                        class="appearance-none block w-full pl-10 pr-12 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        placeholder="{{ auth()->user()->isAdmin() ? 'Cerca per campagna, publisher o database' : 'Cerca per campagna o database' }}"
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

                            <!-- Anno, Mese, Tipologia in una riga -->
                            <div class="grid sm:grid-cols-3 gap-6">
                                <!-- Anno Consuntivo -->
                                <div>
                                    <label for="statement_year" class="block text-md font-medium text-gray-700">Anno
                                        Consuntivo</label>
                                    <select name="statement_year" id="statement_year"
                                        class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-custom-activeItem focus:ring-custom-activeItem sm:text-md">
                                        <option value="">Tutti gli anni</option>
                                        @foreach ($years as $year)
                                            <option value="{{ $year }}"
                                                {{ request('statement_year') == $year ? 'selected' : '' }}>
                                                {{ $year }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Mese Consuntivo -->
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

                                <!-- Tipologia -->
                                <div>
                                    <label for="revenue_type"
                                        class="block text-md font-medium text-gray-700">Tipologia</label>
                                    <select name="revenue_type" id="revenue_type"
                                        class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-custom-activeItem focus:ring-custom-activeItem sm:text-md">
                                        <option value="">Tutte le tipologie</option>
                                        <option value="cpl" {{ request('revenue_type') == 'cpl' ? 'selected' : '' }}>CPL
                                        </option>
                                        <option value="cpc" {{ request('revenue_type') == 'cpc' ? 'selected' : '' }}>CPC
                                        </option>
                                        <option value="cpm" {{ request('revenue_type') == 'cpm' ? 'selected' : '' }}>CPM
                                        </option>
                                        <option value="tmk" {{ request('revenue_type') == 'tmk' ? 'selected' : '' }}>TMK
                                        </option>
                                        <option value="crg" {{ request('revenue_type') == 'crg' ? 'selected' : '' }}>CRG
                                        </option>
                                        <option value="cpa" {{ request('revenue_type') == 'cpa' ? 'selected' : '' }}>CPA
                                        </option>
                                        <option value="sms" {{ request('revenue_type') == 'sms' ? 'selected' : '' }}>SMS
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Bottoni -->
                            <div class="flex justify-end space-x-4">
                                @if (request()->hasAny(['search', 'statement_month', 'revenue_type']))
                                    <a href="{{ route('statements.details') }}"
                                        class=" inline-flex items-center justify-center mt-7 px-4 py-2 rounded-xl border border-gray-200 text-gray-700 bg-white hover:bg-gray-50">
                                        <i data-lucide="x" class="h-5 w-5 mr-2"></i>
                                        Rimuovi Filtri
                                    </a>
                                @endif
                                <button type="submit"
                                    class=" inline-flex items-center justify-center px-4 mt-7 py-2 rounded-xl bg-custom-activeItem text-white hover:bg-custom-activeItem/90">
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
                                    {{ number_format($stat['fatturazione'], 2, ',', '.') }}
                                </span>
                                <span class="ml-1 text-xl text-gray-500">€</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="mt-8 flex flex-col">
            <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-md ring-1 ring-black ring-opacity-5 rounded-xl">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Anno</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Mese Fatturazione</th>
                                    @if (auth()->user()->role->isAdmin())
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nome Publisher</th>
                                    @endif
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nome Database</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nome Campagna</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipologia</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quantità</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Pay</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Importo</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Info</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($statements as $statement)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-900">
                                            {{ $statement->statement_year }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-900">
                                            {{ \Carbon\Carbon::create()->month($statement->statement_month)->locale('it')->translatedFormat('F') }}
                                            @if ($statement->statement_month != $statement->competence_month)
                                                <br><span class="text-xs text-gray-500">Competenza:
                                                    {{ \Carbon\Carbon::create()->month($statement->competence_month)->locale('it')->translatedFormat('F') }}
                                                </span>
                                            @endif
                                        </td>

                                        @if (auth()->user()->role->isAdmin())
                                            <td class="px-6 py-4 whitespace-nowrap text-md text-gray-900">
                                                {{ $statement->publisher->company_name ?? 'N/A' }}
                                            </td>
                                        @endif

                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-900">
                                            {{ $statement->subPublisher->display_name ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-900">
                                            {{ $statement->campaign_name }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-md">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $statement->revenue_type === 'cpl' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $statement->revenue_type === 'cpc' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $statement->revenue_type === 'cpm' ? 'bg-purple-100 text-purple-800' : '' }}
                                                {{ $statement->revenue_type === 'tmk' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $statement->revenue_type === 'crg' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $statement->revenue_type === 'cpa' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                                {{ $statement->revenue_type === 'sms' ? 'bg-pink-100 text-pink-800' : '' }}">
                                                {{ strtoupper($statement->revenue_type) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-900">
                                            {{ number_format($statement->validated_quantity, 0, ',', '.') }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-900">
                                            {{ number_format($statement->pay_per_unit, 2, ',', '.') }} €
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-900">
                                            {{ number_format($statement->total_amount, 2, ',', '.') }} €
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-900">
                                            @if ($statement->sending_date || $statement->notes)
                                                <button
                                                    @click="openInfoModal({{ json_encode([
                                                        'sending_date' => $statement->sending_date,
                                                        'notes' => $statement->notes,
                                                    ]) }})"
                                                    class="text-custom-activeItem hover:text-custom-activeItem/80">
                                                    <i data-lucide="info" class="h-5 w-5"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-6 py-4 text-center text-md text-gray-500">
                                            Nessun consuntivo trovato
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $statements->withQueryString()->links() }}
            </div>
        </div>

        <!-- Info Modal -->
        <div x-show="showInfoModal" x-cloak class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="showInfoModal"
                        class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                    Informazioni Aggiuntive
                                </h3>
                                <div class="mt-2 text-left">
                                    <div class="mb-4">
                                        <p class="text-md font-medium text-gray-500">Data Invio</p>
                                        <p class="text-md text-gray-900"
                                            x-text="modalData.sending_date || 'Non specificata'"></p>
                                    </div>
                                    <div>
                                        <p class="text-md font-medium text-gray-500">Note</p>
                                        <p class="text-md text-gray-900" x-text="modalData.notes || 'Nessuna nota'"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6">
                            <button type="button"
                                class="inline-flex w-full justify-center rounded-xl bg-custom-activeItem px-3 py-2 text-md font-semibold text-white shadow-sm hover:bg-custom-activeItem/90"
                                @click="closeInfoModal">
                                Chiudi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function statementsIndex() {
            return {
                loading: false,
                showInfoModal: false,
                modalData: {},

                openInfoModal(data) {
                    this.modalData = data;
                    this.showInfoModal = true;
                },

                closeInfoModal() {
                    this.showInfoModal = false;
                    this.modalData = {};
                },

                async exportData() {
                    try {
                        this.loading = true;
                        const currentUrl = new URL(window.location.href);
                        const queryParams = new URLSearchParams(currentUrl.searchParams).toString();
                        window.location.href = `/statements/export?${queryParams}`;
                    } catch (error) {
                        console.error('Errore durante l\'esportazione:', error);
                        alert('Errore durante l\'esportazione: verifica la console per ulteriori dettagli.');
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
@endpush
