@extends('layouts.dashboard')

@section('title', 'Modifica Utente')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="userEdit()">
        <!-- Header con breadcrumb -->
        <div class="mb-6">
            <nav class="sm:hidden" aria-label="Back">
                <a href="{{ route('users.index') }}"
                    class="flex items-center text-md font-medium text-gray-500 hover:text-gray-700">
                    <i data-lucide="chevron-left" class="flex-shrink-0 -ml-1 mr-1 h-5 w-5 text-gray-400"></i>
                    Torna alla lista
                </a>
            </nav>
            <nav class="hidden sm:flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-4">
                    <li>
                        <div class="flex">
                            <a href="{{ route('users.index') }}"
                                class="text-md font-medium text-gray-500 hover:text-gray-700">
                                Gestione Utenti
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i data-lucide="chevron-right" class="flex-shrink-0 h-5 w-5 text-gray-400"></i>
                            <span class="ml-4 text-md font-medium text-gray-500">
                                Modifica Utente
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    {{ $user->full_name }}
                </h2>
            </div>
        </div>



        <!-- Tabs -->
        <div class="mt-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'profile'"
                    :class="{
                        'border-custom-activeItem text-custom-activeItem': activeTab === 'profile',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'profile'
                    }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-md">
                    Dati Personali
                </button>
                @if ($user->publisher)
                    <button @click="activeTab = 'publisher'"
                        :class="{
                            'border-custom-activeItem text-custom-activeItem': activeTab === 'publisher',
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'publisher'
                        }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-md">
                        Dati Publisher
                    </button>
                @endif
                @if ($user->publisher)
                    <button @click="activeTab = 'publisher-users'"
                        :class="{
                            'border-custom-activeItem text-custom-activeItem': activeTab === 'publisher-users',
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'publisher-users'
                        }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-md">
                        Utenti Publisher
                    </button>
                @endif
            </nav>
        </div>



        <!-- Form Container -->
        <form action="{{ route('users.update', $user) }}" method="POST" class="mt-6">
            @csrf
            @method('PUT')



            <!-- Profilo Utente -->
            <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-300">
                <!-- Status Card -->
                <div class="mt-6 mb-6 bg-custom-card shadow-md rounded-xl">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-6">
                            Stato Account
                        </h3>
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-3">
                            <!-- Email Verification Badge -->
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">
                                    Verifica Email
                                </label>
                                @if ($user->email_verified)
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-md font-medium bg-green-100 text-green-800">
                                        Verificato
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-md font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="-ml-1 mr-1.5 h-2 w-2 text-yellow-400" fill="currentColor"
                                            viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        In attesa di verifica
                                    </span>
                                @endif
                            </div>

                            <!-- Validation Toggle -->
                            <div>
                                <label for="validation_status" class="block text-md font-medium text-gray-700 mb-2">
                                    Validazione Admin
                                </label>
                                <button type="button"
                                    @click="updateValidationStatus('{{ $user->id }}', '{{ route('users.update-validation', $user) }}')"
                                    :class="{ 'bg-green-600': user.is_validated, 'bg-gray-200': !user.is_validated }"
                                    class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                                    <span class="sr-only">Validazione admin</span>
                                    <span aria-hidden="true"
                                        :class="{ 'translate-x-5': user.is_validated, 'translate-x-0': !user.is_validated }"
                                        class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200">
                                    </span>
                                </button>
                            </div>

                            <!-- Active Status Toggle -->
                            <div>
                                <label for="active_status" class="block text-md font-medium text-gray-700 mb-2">
                                    Stato Account
                                </label>
                                <button type="button"
                                    @click="updateActiveStatus('{{ $user->id }}', '{{ route('users.update-active', $user) }}')"
                                    :class="{ 'bg-green-600': user.is_active, 'bg-gray-200': !user.is_active }"
                                    class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                                    <span class="sr-only">Stato account</span>
                                    <span aria-hidden="true"
                                        :class="{ 'translate-x-5': user.is_active, 'translate-x-0': !user.is_active }"
                                        class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200">
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-custom-card shadow-md rounded-xl">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-6">
                            Informazioni Personali
                        </h3>
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                            <!-- Nome -->
                            <div>
                                <label for="first_name" class="block text-md font-medium text-gray-700">
                                    Nome <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="first_name" id="first_name"
                                        class="appearance-none block w-full px-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        value="{{ old('first_name', $user->first_name) }}" required>
                                </div>
                                @error('first_name')
                                    <p class="mt-2 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Cognome -->
                            <div>
                                <label for="last_name" class="block text-md font-medium text-gray-700">
                                    Cognome <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="last_name" id="last_name"
                                        class="appearance-none block w-full px-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        value="{{ old('last_name', $user->last_name) }}" required>
                                </div>
                                @error('last_name')
                                    <p class="mt-2 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="sm:col-span-2">
                                <label for="email" class="block text-md font-medium text-gray-700">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1">
                                    <input type="email" name="email" id="email"
                                        class="appearance-none block w-full px-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        value="{{ old('email', $user->email) }}" required>
                                </div>
                                @error('email')
                                    <p class="mt-2 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Ruolo -->
                            <div class="sm:col-span-2">
                                <label for="role_id" class="block text-md font-medium text-gray-700">
                                    Ruolo <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1">
                                    <select name="role_id" id="role_id"
                                        class="appearance-none block w-full px-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        {{ $user->id === auth()->id() ? 'disabled' : '' }} required>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @if ($user->id === auth()->id())
                                    <p class="mt-2 text-md text-gray-500">
                                        Non puoi modificare il tuo ruolo
                                    </p>
                                @endif
                                @error('role_id')
                                    <p class="mt-2 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Submit Button -->
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('users.index') }}"
                        class="inline-flex justify-center py-2 px-4 border border-gray-200 shadow-sm text-md font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                        Annulla
                    </a>
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-md font-medium rounded-xl text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                        Salva Modifiche
                    </button>
                </div>
            </div>


            <!-- Publisher Info -->
            <div x-show="activeTab === 'publisher'" x-transition:enter="transition ease-out duration-300">
                @if ($user->publisher)
                    <div class="bg-custom-card shadow-md rounded-xl">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-6">
                                Informazioni Publisher
                            </h3>
                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                                <!-- Ragione Sociale -->
                                <div>
                                    <span class="block text-md font-medium text-gray-700">Ragione Sociale</span>
                                    <span class="mt-1 block text-md text-gray-900">
                                        {{ $user->publisher->legal_name }}
                                    </span>
                                </div>

                                <!-- Nome Azienda -->
                                <div>
                                    <span class="block text-md font-medium text-gray-700">Nome Azienda</span>
                                    <span class="mt-1 block text-md text-gray-900">
                                        {{ $user->publisher->company_name }}
                                    </span>
                                </div>

                                <!-- Partita IVA -->
                                <div>
                                    <span class="block text-md font-medium text-gray-700">Partita IVA</span>
                                    <span class="mt-1 block text-md text-gray-900">
                                        {{ $user->publisher->vat_number }}
                                    </span>
                                </div>

                                <!-- Provincia -->
                                <div>
                                    <span class="block text-md font-medium text-gray-700">Provincia</span>
                                    <span class="mt-1 block text-md text-gray-900">
                                        {{ $user->publisher->county }}
                                    </span>
                                </div>

                                <!-- Città -->
                                <div>
                                    <span class="block text-md font-medium text-gray-700">Città</span>
                                    <span class="mt-1 block text-md text-gray-900">
                                        {{ $user->publisher->city }}
                                    </span>
                                </div>

                                <!-- CAP -->
                                <div>
                                    <span class="block text-md font-medium text-gray-700">CAP</span>
                                    <span class="mt-1 block text-md text-gray-900">
                                        {{ $user->publisher->postal_code }}
                                    </span>
                                </div>

                                <!-- IBAN -->
                                <div>
                                    <span class="block text-md font-medium text-gray-700">IBAN</span>
                                    <span class="mt-1 block text-md text-gray-900 uppercase">
                                        {{ $user->publisher->iban }}
                                    </span>
                                </div>

                                <!-- SWIFT -->
                                <div>
                                    <span class="block text-md font-medium text-gray-700">SWIFT</span>
                                    <span class="mt-1 block text-md text-gray-900 uppercase">
                                        {{ $user->publisher->swift }}
                                    </span>
                                </div>
                            </div>
                            <!-- Aggiungiamo il bottone Modifica -->
                            <div class="mt-6 flex justify-end">
                                <a href="{{ route('publishers.edit', $user->publisher->id) }}"
                                    class="inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-md font-medium rounded-xl text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                                    <i data-lucide="edit" class="h-4 w-4 mr-2"></i>
                                    Modifica Publisher
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="alert-triangle" class="h-5 w-5 text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-md font-medium text-yellow-800">
                                    Nessun publisher associato
                                </h3>
                                <div class="mt-2 text-md text-yellow-700">
                                    <p>
                                        Questo utente non ha un publisher associato.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>


            <!-- Publisher Users -->
            <div x-show="activeTab === 'publisher-users'" x-transition:enter="transition ease-out duration-300">
                @if ($user->publisher)
                    <div class="bg-custom-card shadow-md rounded-xl">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="sm:flex sm:items-center">
                                <div class="sm:flex-auto">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                                        Utenti associati a {{ $user->publisher->company_name }}
                                    </h3>
                                    <p class="mt-2 text-md text-gray-700">
                                        Elenco degli utenti che appartengono a questo publisher
                                    </p>
                                </div>
                            </div>

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
                                                    @foreach ($user->publisher->users as $publisherUser)
                                                        <tr>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-md font-medium text-gray-900">
                                                                {{ $publisherUser->full_name }}
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-md text-gray-500">
                                                                {{ $publisherUser->email }}
                                                            </td>
                                                            <!-- Verifica Email -->
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                @if ($publisherUser->email_verified)
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
                                                                @if ($publisherUser->is_validated)
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
                                                                @if ($publisherUser->is_active)
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
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-right text-md font-medium">
                                                                <a href="{{ route('users.edit', $publisherUser) }}"
                                                                    class="text-custom-activeItem hover:text-custom-activeItem/90">
                                                                    <i data-lucide="edit" class="h-5 w-5"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>


        </form>


    </div>
@endsection

@push('scripts')
    <script>
        function userEdit() {
            return {
                activeTab: 'profile',
                user: {
                    is_validated: {{ $user->is_validated ? 'true' : 'false' }},
                    is_active: {{ $user->is_active ? 'true' : 'false' }}
                },

                async updateValidationStatus(userId, url) {
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                is_validated: !this.user.is_validated
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Errore durante l\'aggiornamento dello stato di validazione:', error);
                    }
                },

                async updateActiveStatus(userId, url) {
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                is_active: !this.user.is_active
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Errore durante l\'aggiornamento dello stato attivo:', error);
                    }
                },

                init() {
                    // Se ci sono errori nel form del publisher, mostra quel tab
                    @if ($errors->any() && old('company_name'))
                        this.activeTab = 'publisher';
                    @endif

                    // Verifica se c'è un parametro tab nell'URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const tab = urlParams.get('tab');
                    if (tab && ['profile', 'publisher', 'publisher-users'].includes(tab)) {
                        this.activeTab = tab;
                    }
                }
            }
        }
    </script>
@endpush
