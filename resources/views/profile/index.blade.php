@extends('layouts.dashboard')

@section('title', 'Profilo')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
        activeTab: 'profile',
        passwordChecks: {
            minLength: false,
            uppercase: false,
            lowercase: false,
            number: false,
            special: false
        },
        checkPassword(password) {
            this.passwordChecks = {
                minLength: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
        }
    }">
        <!-- Header -->
        <div class="mb-8 text-left">
            <h2 class="text-3xl font-bold text-gray-900">
                Profilo Utente
            </h2>
        </div>

        <!-- Tabs -->
        <div class="mb-8">
            <nav class="flex justify-left space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'profile'"
                    :class="{
                        'text-blue-600 border-b-2 border-blue-600': activeTab === 'profile',
                        'text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'profile'
                    }"
                    class="pb-4 px-1 font-medium text-md transition-colors duration-200">
                    Dati Personali
                </button>
                @if (auth()->user()->publisher)
                    <button @click="activeTab = 'publisher'"
                        :class="{
                            'text-blue-600 border-b-2 border-blue-600': activeTab === 'publisher',
                            'text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'publisher'
                        }"
                        class="pb-4 px-1 font-medium text-md transition-colors duration-200">
                        Dati Publisher
                    </button>
                @endif
            </nav>
        </div>

        <!-- Personal Info Form -->
        <div x-show="activeTab === 'profile'" class="space-y-8">
            <div class="bg-white shadow-lg rounded-2xl">
                <div class="p-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-8">
                        Informazioni Personali
                    </h3>
                    <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <!-- First Name -->
                                <div>
                                    <label for="first_name" class="block text-md font-medium text-gray-700 mb-2">
                                        Nome <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="first_name" id="first_name"
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        value="{{ old('first_name', $user->first_name) }}" required>
                                    @error('first_name')
                                        <p class="mt-2 text-md text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Last Name -->
                                <div>
                                    <label for="last_name" class="block text-md font-medium text-gray-700 mb-2">
                                        Cognome <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="last_name" id="last_name"
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        value="{{ old('last_name', $user->last_name) }}" required>
                                    @error('last_name')
                                        <p class="mt-2 text-md text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6">
                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-md font-medium text-gray-700 mb-2">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" name="email" id="email"
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                        value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <p class="mt-2 text-md text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <div class="flex items-end justify-end">
                                    <button type="submit"
                                        class="w-sm px-6 py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                                        Salva Modifiche
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Profile Controls -->
            <div class="bg-white shadow-lg rounded-2xl">
                <div class="p-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-8">Controlli Profilo</h3>

                    <div class="space-y-8">
                        <!-- Email Notifications -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="font-medium text-gray-900">Notifiche Email</h4>
                                    <p class="text-md text-gray-500">Gestisci la ricezione delle email dalla piattaforma.
                                    </p>
                                </div>
                                <form action="{{ route('profile.notifications.toggle') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $user->can_receive_email ? 'bg-blue-600' : 'bg-gray-200' }}">
                                        <span class="sr-only">Abilita notifiche email</span>
                                        <span
                                            class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $user->can_receive_email ? 'translate-x-5' : '' }}">
                                        </span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        @if (auth()->user()->isPublisher())
                        <div class="border-t border-gray-100"></div>

                        <!-- Account Deactivation -->
                        
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Disattivazione Account</h4>
                                        <p class="text-md text-gray-500">Questa azione richiederà
                                            l'intervento di un amministratore per essere annullata.</p>
                                    </div>
                                    <button type="button" @click="$dispatch('open-modal', 'confirm-deactivation')"
                                        class="relative inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200">
                                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        Disattiva Account
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Deactivation Confirmation Modal -->
            <div x-data="{ show: false }" x-show="show"
                x-on:open-modal.window="if ($event.detail === 'confirm-deactivation') show = true"
                x-on:close-modal.window="show = false" x-on:keydown.escape.window="show = false"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed z-10 inset-0 overflow-y-auto" style="display: none;">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div
                        class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Conferma Disattivazione Account
                                </h3>
                                <div class="mt-2">
                                    <p class="text-md text-gray-500">
                                        Sei sicuro di voler disattivare il tuo account? Questa azione ti disconnetterà
                                        immediatamente e non potrai più accedere alla piattaforma. Per riattivare l'account
                                        sarà necessario contattare un amministratore.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <form action="{{ route('profile.deactivate') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-md">
                                    Disattiva Account
                                </button>
                            </form>
                            <button type="button" @click="show = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-md">
                                Annulla
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Publisher Info Section -->
        <div x-show="activeTab === 'publisher'" class="mt-6" x-cloak>
            @if ($publisher)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-6">
                            Informazioni Publisher
                        </h3>

                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-md font-medium text-gray-500">Partita IVA</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->vat_number }}</dd>
                            </div>

                            <div>
                                <dt class="text-md font-medium text-gray-500">Ragione Sociale</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->legal_name }}</dd>
                            </div>

                            <div>
                                <dt class="text-md font-medium text-gray-500">Nome Azienda</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->company_name }}</dd>
                            </div>

                            <div>
                                <dt class="text-md font-medium text-gray-500">Sito Web</dt>
                                <dd class="mt-1 text-md text-gray-900">
                                    <a href="{{ $publisher->website }}" target="_blank"
                                        class="text-blue-600 hover:text-blue-500">
                                        {{ $publisher->website }}
                                    </a>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-md font-medium text-gray-500">Provincia</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->county }}</dd>
                            </div>

                            <div>
                                <dt class="text-md font-medium text-gray-500">Città</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->city }}</dd>
                            </div>

                            <div>
                                <dt class="text-md font-medium text-gray-500">CAP</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->postal_code }}</dd>
                            </div>

                            <div>
                                <dt class="text-md font-medium text-gray-500">IBAN</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->iban }}</dd>
                            </div>

                            <div>
                                <dt class="text-md font-medium text-gray-500">SWIFT</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->swift }}</dd>
                            </div>
                        </dl>

                        <div class="mt-6">
                            <div class="rounded-md bg-blue-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-md text-blue-700">
                                            Per modificare i tuoi dati, scrivi a pannello@triboo.it
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-md bg-yellow-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-md font-medium text-yellow-800">
                                Nessun publisher associato
                            </h3>
                            <div class="mt-2 text-md text-yellow-700">
                                <p>
                                    Non risulta alcun publisher associato al tuo account.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
