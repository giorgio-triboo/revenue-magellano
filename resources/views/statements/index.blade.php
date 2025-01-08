@extends('layouts.dashboard')

@section('title', 'Gestione Utenti')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="userManagement()">
        <!-- Header con pulsante export -->
        <div class="md:flex md:items-center md:justify-between mb-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    Gestione Utenti
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('users.export') }}"
                    class="inline-flex justify-end items-center px-4 py-2 rounded-xl bg-custom-btn text-white hover:bg-custom-btn">
                    <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                    Scarica Dati
                </a>
            </div>
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
                            placeholder="Cerca per nome, email ..." value="{{ request('search') }}">
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
                        <span class="text-sm font-medium text-gray-700">Stato utenti:</span>
                        <div class="flex space-x-2">
                            <button type="submit" name="status" value="active"
                                class="px-3 py-1 rounded-full text-sm font-medium {{ request('status', 'active') === 'active' ? 'bg-custom-activeItem text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                <div class="flex items-center space-x-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-green-400"></span>
                                    <span>Attivi</span>
                                </div>
                            </button>
                            <button type="submit" name="status" value="deleted"
                                class="px-3 py-1 rounded-full text-sm font-medium {{ request('status') === 'deleted' ? 'bg-custom-activeItem text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                <div class="flex items-center space-x-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-red-400"></span>
                                    <span>Eliminati</span>
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

        <!-- Users Table -->
        <div class="mt-8 flex flex-col">
            <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-md rounded-xl bg-custom-card">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Utente
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Publisher
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Verifica Email
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Validazione Admin
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        Stato Account
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Azioni</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-md font-medium text-gray-900">
                                            {{ $user->first_name }} {{ $user->last_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-500">
                                            {{ $user->email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-md text-gray-500">
                                            {{ $user->publisher?->company_name }}
                                        </td>
                                        <!-- Verifica Email -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($user->email_verified)
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Verificata
                                                </span>
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    In attesa
                                                </span>
                                            @endif
                                        </td>
                                        <!-- Validazione Admin -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($user->is_validated)
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Validato
                                                </span>
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    In attesa
                                                </span>
                                            @endif
                                        </td>
                                        <!-- Stato Account -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($user->is_active)
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Attivo
                                                </span>
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Non attivo
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-right text-md font-medium">
                                            <div class="flex justify-end space-x-2">
                                                @if ($user->trashed())
                                                    <button @click="restoreUser('{{ $user->id }}')"
                                                        class="text-green-600 hover:text-green-700"
                                                        title="Ripristina utente">
                                                        <i data-lucide="refresh-cw" class="h-5 w-5"></i>
                                                    </button>
                                                @else
                                                    <button @click="showDetails({{ $user->id }})"
                                                        class="text-custom-activeItem hover:text-custom-activeItem/90">
                                                        <i data-lucide="eye" class="h-5 w-5"></i>
                                                    </button>
                                                    <a href="{{ route('users.edit', $user) }}"
                                                        class="text-custom-activeItem hover:text-custom-activeItem/90">
                                                        <i data-lucide="edit" class="h-5 w-5"></i>
                                                    </a>
                                                    @if ($user->id !== auth()->id())
                                                        <button @click="confirmDelete({{ $user->id }})"
                                                            class="text-red-600 hover:text-red-700">
                                                            <i data-lucide="trash-2" class="h-5 w-5"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="px-6 py-4 whitespace-nowrap text-md text-gray-500 text-center">
                                            Nessun utente trovato
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
            {{ $users->links() }}
        </div>

        <!-- User Details Modal -->
        <div x-show="showModal" class="fixed z-10 inset-0 overflow-y-auto" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    @click="showModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <div class="absolute top-0 right-0 pt-4 pr-4">
                        <button type="button" @click="showModal = false"
                            class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                            <span class="sr-only">Chiudi</span>
                            <i data-lucide="x" class="h-6 w-6"></i>
                        </button>
                    </div>

                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg leading-6 font-medium text-gray-900"
                                    x-text="userDetails ? userDetails.first_name + ' ' + userDetails.last_name : ''"></h3>
                            </div>

                            <!-- Status Cards Section -->
                            <div class="mt-4 bg-gray-50 rounded-lg p-4">
                                <h4 class="text-md font-medium text-gray-500 mb-3">Stato Account</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <!-- Email Verification Status -->
                                    <div class="bg-white p-3 rounded-lg shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-md text-gray-500">Email</span>
                                            <span x-show="userDetails && userDetails.email_verified"
                                                class="flex items-center text-green-600">
                                                <i data-lucide="check-circle" class="h-4 w-4 mr-1"></i>
                                                <span class="text-xs">Verificata</span>
                                            </span>
                                            <span x-show="userDetails && !userDetails.email_verified"
                                                class="flex items-center text-yellow-600">
                                                <i data-lucide="clock" class="h-4 w-4 mr-1"></i>
                                                <span class="text-xs">In attesa</span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Admin Validation Status -->
                                    <div class="bg-white p-3 rounded-lg shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-md text-gray-500">Validazione</span>
                                            <span x-show="userDetails && userDetails.is_validated"
                                                class="flex items-center text-green-600">
                                                <i data-lucide="shield-check" class="h-4 w-4 mr-1"></i>
                                                <span class="text-xs">Validato</span>
                                            </span>
                                            <span x-show="userDetails && !userDetails.is_validated"
                                                class="flex items-center text-yellow-600">
                                                <i data-lucide="shield" class="h-4 w-4 mr-1"></i>
                                                <span class="text-xs">In attesa</span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Account Status -->
                                    <div class="bg-white p-3 rounded-lg shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-md text-gray-500">Stato</span>
                                            <span x-show="userDetails && userDetails.is_active"
                                                class="flex items-center text-green-600">
                                                <i data-lucide="activity" class="h-4 w-4 mr-1"></i>
                                                <span class="text-xs">Attivo</span>
                                            </span>
                                            <span x-show="userDetails && !userDetails.is_active"
                                                class="flex items-center text-red-600">
                                                <i data-lucide="x-circle" class="h-4 w-4 mr-1"></i>
                                                <span class="text-xs">Inattivo</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Information -->
                            <div class="mt-4">
                                <h4 class="text-md font-medium text-gray-500 mb-3">Informazioni Personali</h4>
                                <div class="bg-white rounded-lg shadow-sm">
                                    <dl>
                                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                            <dt class="text-md font-medium text-gray-500">Email</dt>
                                            <dd class="mt-1 text-md text-gray-900 sm:mt-0 sm:col-span-2"
                                                x-text="userDetails?.email"></dd>
                                        </div>
                                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 bg-gray-50">
                                            <dt class="text-md font-medium text-gray-500">Ruolo</dt>
                                            <dd class="mt-1 text-md text-gray-900 sm:mt-0 sm:col-span-2"
                                                x-text="userDetails?.role?.name"></dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Publisher Information (if exists) -->
                            <div class="mt-4" x-show="userDetails?.publisher">
                                <h4 class="text-md font-medium text-gray-500 mb-3">Informazioni Publisher</h4>
                                <div class="bg-white rounded-lg shadow-sm">
                                    <dl>
                                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                            <dt class="text-md font-medium text-gray-500">Ragione Sociale</dt>
                                            <dd class="mt-1 text-md text-gray-900 sm:mt-0 sm:col-span-2"
                                                x-text="userDetails?.publisher?.legal_name"></dd>
                                        </div>
                                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 bg-gray-50">
                                            <dt class="text-md font-medium text-gray-500">Nome Azienda</dt>
                                            <dd class="mt-1 text-md text-gray-900 sm:mt-0 sm:col-span-2"
                                                x-text="userDetails?.publisher?.company_name"></dd>
                                        </div>
                                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                            <dt class="text-md font-medium text-gray-500">Partita IVA</dt>
                                            <dd class="mt-1 text-md text-gray-900 sm:mt-0 sm:col-span-2"
                                                x-text="userDetails?.publisher?.vat_number"></dd>
                                        </div>
                                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 bg-gray-50">
                                            <dt class="text-md font-medium text-gray-500">Sito Web</dt>
                                            <dd class="mt-1 text-md text-gray-900 sm:mt-0 sm:col-span-2">
                                                <a :href="userDetails?.publisher?.website"
                                                    class="text-custom-activeItem hover:text-custom-activeItem/90"
                                                    target="_blank" x-text="userDetails?.publisher?.website"></a>
                                            </dd>
                                        </div>
                                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                            <dt class="text-md font-medium text-gray-500">Indirizzo</dt>
                                            <dd class="mt-1 text-md text-gray-900 sm:mt-0 sm:col-span-2"
                                                x-text="formatAddress(userDetails?.publisher)"></dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <a :href="userDetails ? `/users/${userDetails.id}/edit` : '#'"
                            class="inline-flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                            Modifica
                        </a>
                        <button type="button" @click="showModal = false"
                            class="mt-3 sm:mt-0 sm:mr-3 inline-flex justify-center py-2 px-4 border border-gray-200 rounded-xl shadow-sm text-md font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                            Chiudi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" class="fixed z-10 inset-0 overflow-y-auto" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    @click="showDeleteModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block align-bottom bg-custom-card rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-md transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="alert-triangle" class="h-6 w-6 text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Conferma eliminazione
                            </h3>
                            <div class="mt-2">
                                <p class="text-md text-gray-500">
                                    Sei sicuro di voler eliminare questo utente? Questa azione non può essere annullata.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button"
                            class="inline-flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            @click="deleteUser">
                            Elimina
                        </button>
                        <button type="button"
                            class="mr-3 inline-flex justify-center py-2 px-4 border border-gray-200 rounded-xl shadow-sm text-md font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem"
                            @click="showDeleteModal = false">
                            Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function userManagement() {
            return {
                searchQuery: '{{ request('search') }}',
                searchTimeout: null,
                showModal: false,
                userDetails: null,
                showDeleteModal: false,
                userIdToDelete: null,

                async restoreUser(userId) {
                    if (confirm('Sei sicuro di voler ripristinare questo utente?')) {
                        try {
                            const response = await fetch(`/users/${userId}/restore`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            });

                            const data = await response.json();
                            if (data.success) {
                                window.location.reload();
                            } else {
                                console.error('Errore durante il ripristino:', data.message);
                            }
                        } catch (error) {
                            console.error('Errore durante il ripristino:', error);
                        }
                    }
                },

                showDetails(userId) {
                    fetch(`/users/${userId}/details`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.userDetails = data.data;
                                this.showModal = true;
                            }
                        })
                        .catch(error => console.error("Errore nella richiesta AJAX:", error));
                },

                formatAddress(publisher) {
                    if (!publisher) return '';
                    return `${publisher.city} (${publisher.county}), ${publisher.postal_code}`;
                },

                confirmDelete(userId) {
                    this.userIdToDelete = userId;
                    this.showDeleteModal = true;
                },

                deleteUser() {
                    if (!this.userIdToDelete) return;

                    fetch(`/users/${this.userIdToDelete}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.showDeleteModal = false;
                                this.userIdToDelete = null;
                                window.location.reload();
                            } else {
                                console.error("Errore nell'eliminazione dell'utente:", data.message);
                            }
                        })
                        .catch(error => console.error("Errore nella richiesta DELETE:", error));
                }
            }
        }
    </script>
@endpush
