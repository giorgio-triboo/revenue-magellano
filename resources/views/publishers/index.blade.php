@extends('layouts.dashboard')

@section('title', 'Lista Publishers')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="publishersIndex()">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:tracking-tight">
                    Lista Publishers
                </h2>
            </div>
            @if ($canExport)
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <button @click="exportData" :disabled="loading"
                        class="inline-flex justify-end items-center px-4 py-2 rounded-xl bg-custom-btn text-white hover:bg-custom-btn">
                        <i x-show="!loading" data-lucide="download" class="h-5 w-5 mr-2"></i>
                        <i x-show="loading" data-lucide="loader-2" class="h-5 w-5 mr-2 animate-spin"></i>
                        Scarica Dati
                    </button>
                </div>
            @endif
        </div>

        <!-- Search Bar e Filtri -->
        <div class="mt-4">
            <div class="max-w-xl">
                <form action="{{ request()->url() }}" method="GET" class="space-y-4">
                    <!-- Search input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input type="text" name="search" id="search"
                            class="appearance-none block w-full pl-10 pr-12 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                            placeholder="Cerca per nome del publisher.." value="{{ request('search') }}">
                        <div class="absolute inset-y-0 right-0 flex items-center">
                            @if (request('search'))
                                <a href="{{ request()->url() }}" class="pr-2">
                                    <i data-lucide="x-circle" class="h-5 w-5 text-gray-400 hover:text-gray-500"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700">Stato publisher:</span>
                        <div class="flex space-x-2">
                            <button type="submit" name="status" value="active"
                                class="px-3 py-1 rounded-full text-sm font-medium {{ request('status', 'active') === 'active' ? 'bg-custom-activeItem text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                <div class="flex items-center space-x-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-green-400"></span>
                                    <span>Attivi</span>
                                </div>
                            </button>
                            <button type="submit" name="status" value="inactive"
                                class="px-3 py-1 rounded-full text-sm font-medium {{ request('status') === 'inactive' ? 'bg-custom-activeItem text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                <div class="flex items-center space-x-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-red-400"></span>
                                    <span>Non attivi</span>
                                </div>
                            </button>
                            <button type="submit" name="status" value="all"
                                class="px-3 py-1 rounded-full text-sm font-medium {{ request('status') === 'all' ? 'bg-custom-activeItem text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                <div class="flex items-center space-x-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-gray-400"></span>
                                    <span>Tutti</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Publishers Table -->
        <div class="mt-8 flex flex-col">
            <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-md ring-1 ring-black ring-opacity-5 rounded-xl bg-custom-card">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-sm font-bold text-gray-500 uppercase tracking-wider">
                                        <div class="group inline-flex items-center">
                                            Nome Azienda
                                        </div>
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-sm font-bold text-gray-500 uppercase tracking-wider">
                                        Database
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-sm font-bold text-gray-500 uppercase tracking-wider">
                                        Stato
                                    </th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Azioni</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($publishers as $publisher)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-md font-medium text-gray-900">
                                            {{ $publisher->company_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-500">
                                            {{ $publisher->sub_publishers_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-500">
                                            <span
                                                class="inline-flex rounded-xl px-2.5 py-0.5 text-sm font-medium {{ $publisher->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $publisher->is_active ? 'Attivo' : 'Non attivo' }}
                                            </span>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-md font-medium sm:pr-6">
                                            <div class="flex justify-end space-x-2">
                                                <!-- Visualizza -->
                                                <a href="{{ route('publishers.show', $publisher) }}"
                                                    class="text-custom-activeItem hover:text-custom-activeItem/90"
                                                    title="Visualizza dettagli">
                                                    <i data-lucide="eye" class="h-5 w-5"></i>
                                                </a>

                                                @if ($canUpdate)
                                                    <!-- Modifica -->
                                                    <a href="{{ route('publishers.edit', $publisher) }}"
                                                        class="text-custom-activeItem hover:text-custom-activeItem/90"
                                                        title="Modifica">
                                                        <i data-lucide="edit" class="h-5 w-5"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4"
                                            class="px-6 py-4 whitespace-nowrap text-md text-gray-500 text-center">
                                            Nessun publisher trovato
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $publishers->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function publishersIndex() {
            return {
                loading: false,
                filters: {
                    search: @json($filters['search'] ?? ''),
                    status: @json($filters['status'] ?? ''),
                    sort: @json($filters['sort'] ?? 'company_name'),
                    direction: @json($filters['direction'] ?? 'asc')
                },

                async exportData() {
                    try {
                        this.loading = true;
                        const queryParams = new URLSearchParams(this.filters).toString();
                        window.location.href = `/publishers/export?${queryParams}`;
                    } catch (error) {
                        console.error('Errore durante l\'esportazione:', error);
                        alert('Si è verificato un errore durante l\'esportazione dei dati');
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
@endpush