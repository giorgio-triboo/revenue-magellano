@extends('layouts.auth') @section('title', 'Registrazione')
@section('content')
    <div x-data="registrationForm()"
        class="min-h-[calc(100vh-200px)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-xl">
            <div class="bg-custom-card py-8 px-4 shadow-md sm:rounded-xl sm:px-10">
                <!-- Header -->
                <div class="sm:mx-auto sm:w-full sm:max-w-md">
                    <h2 class="mt-2 text-center text-3xl font-bold text-gray-900">
                        Registrazione
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Completa tutti i passaggi per accedere alla piattaforma
                    </p>
                </div>

                <!-- Progress Bar -->
                <div class="mt-12 mb-8">
                    <nav aria-label="Progress">
                        <div class="flex justify-between">
                            <!-- Step 1: Partita IVA -->
                            <div class="relative flex flex-col items-center">
                                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2"
                                    :class="{
                                        'bg-custom-activeItem border-custom-activeItem text-white': currentStep === 1,
                                        'bg-custom-activeItem border-custom-activeItem text-white': currentStep > 1,
                                        'border-gray-200 text-gray-400': currentStep < 1
                                    }">
                                    1
                                </div>
                                <div class="mt-2 text-sm"
                                    :class="{
                                        'text-custom-activeItem font-medium': currentStep === 1,
                                        'text-custom-activeItem': currentStep > 1,
                                        'text-gray-500': currentStep < 1
                                    }">
                                    Partita IVA
                                </div>
                            </div>

                            <!-- Step 2: Dati Aziendali -->
                            <div class="relative flex flex-col items-center">
                                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2"
                                    :class="{
                                        'bg-custom-activeItem border-custom-activeItem text-white': [2, 3, 4].includes(
                                            currentStep),
                                        'bg-custom-activeItem border-custom-activeItem text-white': currentStep > 4,
                                        'border-gray-200 text-gray-400': currentStep < 2
                                    }">
                                    2
                                </div>
                                <div class="mt-2 text-sm"
                                    :class="{
                                        'text-custom-activeItem font-medium': [2, 3, 4].includes(currentStep),
                                        'text-custom-activeItem': currentStep > 4,
                                        'text-gray-500': currentStep < 2
                                    }">
                                    Dati Aziendali
                                </div>
                            </div>

                            <!-- Step 3: Dati Personali -->
                            <div class="relative flex flex-col items-center">
                                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2"
                                    :class="{
                                        'bg-custom-activeItem border-custom-activeItem text-white': currentStep === 5,
                                        'bg-custom-activeItem border-custom-activeItem text-white': currentStep > 5,
                                        'border-gray-200 text-gray-400': currentStep < 5
                                    }">
                                    3
                                </div>
                                <div class="mt-2 text-sm"
                                    :class="{
                                        'text-custom-activeItem font-medium': currentStep === 5,
                                        'text-custom-activeItem': currentStep > 5,
                                        'text-gray-500': currentStep < 5
                                    }">
                                    Dati Personali
                                </div>
                            </div>

                            <!-- Step 4: Credenziali -->
                            <div class="relative flex flex-col items-center">
                                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2"
                                    :class="{
                                        'bg-custom-activeItem border-custom-activeItem text-white': currentStep === 6,
                                        'border-gray-200 text-gray-400': currentStep < 6
                                    }">
                                    4
                                </div>
                                <div class="mt-2 text-sm"
                                    :class="{
                                        'text-custom-activeItem font-medium': currentStep === 6,
                                        'text-gray-500': currentStep < 6
                                    }">
                                    Credenziali
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>

                <!-- Form Container -->
                <form @submit.prevent="submitForm" class="mt-8 space-y-6">
                    @csrf
                    <!-- Step 1: Partita IVA -->
                    <div x-show="currentStep === 1" x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="space-y-4">
                            <!-- Country Code Select -->
                            <div>
                                <label for="country_code" class="block text-sm font-medium text-gray-700">
                                    Paese <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="globe" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <select id="country_code" 
                                            x-model="formData.country_code"
                                            @change="formData.vat_number = ''; errors = {};"
                                            class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                            :class="{ 'border-red-300': errors.country_code }">
                                        @if(isset($countries) && is_array($countries))
                                            @foreach($countries as $code => $name)
                                                <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center" x-show="errors.country_code">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.country_code" x-text="errors.country_code" class="mt-2 text-sm text-red-600"></p>
                            </div>
                        
                            <!-- VAT Number Input -->
                            <div>
                                <label for="vat_number" class="block text-sm font-medium text-gray-700">
                                    Numero Partita IVA <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="file-text" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="vat_number" 
                                           type="text" 
                                           x-model="formData.vat_number"
                                           @input="$event.target.value = $event.target.value.replace(/[^\dA-Z]/g, '')"
                                           class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm uppercase"
                                           :class="{
                                               'border-red-300': errors.vat_number,
                                           }"
                                           :placeholder="'Inserisci il numero (senza ' + formData.country_code + ')'"
                                    />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <i data-lucide="x-circle" x-show="errors.vat_number" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.vat_number" x-text="errors.vat_number" class="mt-2 text-sm text-red-600"></p>
                                <p class="mt-2 text-sm text-gray-500">
                                    Inserisci solo il numero senza il prefisso del paese
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Dati Aziendali -->
                    <div x-show="currentStep === 2" x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="space-y-6">
                            <!-- Nome Azienda -->
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700">
                                    Nome Azienda <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="building" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="company_name" type="text" x-model="formData.company_name"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': errors.company_name }"
                                        placeholder="Nome della tua azienda" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                        x-show="errors.company_name">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.company_name" x-text="errors.company_name"
                                    class="mt-2 text-sm text-red-600"></p>
                            </div>

                            <!-- Ragione Sociale -->
                            <div>
                                <label for="legal_name" class="block text-sm font-medium text-gray-700">
                                    Ragione Sociale <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="briefcase" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="legal_name" type="text" x-model="formData.legal_name"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': errors.legal_name }"
                                        placeholder="Ragione sociale completa" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                        x-show="errors.legal_name">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.legal_name" x-text="errors.legal_name"
                                    class="mt-2 text-sm text-red-600"></p>
                            </div>

                            
                        </div>
                    </div>

                    <!-- Step 3: Indirizzo -->
                    <div x-show="currentStep === 3" x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="space-y-6">
                            <!-- Provincia -->
                            <div>
                                <label for="county" class="block text-sm font-medium text-gray-700">
                                    Provincia <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="map-pin" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="county" type="text" x-model="formData.county"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': errors.county }" placeholder="Es: Milano" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center" x-show="errors.county">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.county" x-text="errors.county" class="mt-2 text-sm text-red-600"></p>
                            </div>

                            <!-- Città -->
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700">
                                    Città <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="building" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="city" type="text" x-model="formData.city"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': errors.city }" placeholder="Es: Milano" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center" x-show="errors.city">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.city" x-text="errors.city" class="mt-2 text-sm text-red-600"></p>
                            </div>

                            <!-- CAP -->
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700">
                                    CAP <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="map" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="postal_code" type="text" x-model="formData.postal_code" maxlength="5"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': errors.postal_code }" placeholder="Es: 20100" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                        x-show="errors.postal_code">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.postal_code" x-text="errors.postal_code"
                                    class="mt-2 text-sm text-red-600"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Dati Bancari -->
                    <div x-show="currentStep === 4" x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="space-y-6">
                            <!-- IBAN -->
                            <div>
                                <label for="iban" class="block text-sm font-medium text-gray-700">
                                    IBAN <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="credit-card" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="iban" type="text" x-model="formData.iban" maxlength="27"
                                        @input="$event.target.value = $event.target.value.toUpperCase()"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm uppercase"
                                        :class="{ 'border-red-300': errors.iban }"
                                        placeholder="IT00A0000000000000000000000" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center" x-show="errors.iban">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.iban" x-text="errors.iban" class="mt-2 text-sm text-red-600"></p>
                            </div>

                            <!-- SWIFT -->
                            <div>
                                <label for="swift" class="block text-sm font-medium text-gray-700">
                                    SWIFT <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="credit-card" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="swift" type="text" x-model="formData.swift" maxlength="11"
                                        @input="$event.target.value = $event.target.value.toUpperCase()"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm uppercase"
                                        :class="{ 'border-red-300': errors.swift }" placeholder="Es: BAPPIT21000" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center" x-show="errors.swift">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.swift" x-text="errors.swift" class="mt-2 text-sm text-red-600"></p>
                                <p class="mt-2 text-sm text-gray-500">
                                    Inserisci il codice SWIFT/BIC della tua banca
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Dati Personali -->
                    <div x-show="currentStep === 5" x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="space-y-6">

                            <!-- Messaggio per partita IVA esistente -->
                            <div x-show="publisherExists" x-cloak
                                class="mb-6 rounded-md bg-blue-50 p-4 border border-blue-200">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-blue-800">
                                            Questa partita IVA è già registrata nel sistema.<br> Prosegui con l'inserimento
                                            dei tuoi
                                            dati personali per associarti all'azienda.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- Nome -->
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700">
                                    Nome <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="user" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="first_name" type="text" x-model="formData.first_name"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': errors.first_name }" placeholder="Il tuo nome" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                        x-show="errors.first_name">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.first_name" x-text="errors.first_name"
                                    class="mt-2 text-sm text-red-600"></p>
                            </div>

                            <!-- Cognome -->
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700">
                                    Cognome <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="user" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="last_name" type="text" x-model="formData.last_name"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': errors.last_name }" placeholder="Il tuo cognome" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                        x-show="errors.last_name">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.last_name" x-text="errors.last_name" class="mt-2 text-sm text-red-600">
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Credenziali -->
                    <div x-show="currentStep === 6" x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="space-y-6">
                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="email" type="email" x-model="formData.email"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': errors.email }" placeholder="La tua email" />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center" x-show="errors.email">
                                        <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
                                    </div>
                                </div>
                                <p x-show="errors.email" x-text="errors.email" class="mt-2 text-sm text-red-600"></p>
                            </div>

                            <!-- Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="password" :type="showPassword ? 'text' : 'password'"
                                        x-model="formData.password" @input="checkPasswordStrength"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': errors.password }" placeholder="La tua password" />
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <i data-lucide="eye" x-show="!showPassword" class="h-5 w-5 text-gray-400"></i>
                                        <i data-lucide="eye-off" x-show="showPassword" class="h-5 w-5 text-gray-400"></i>
                                    </button>
                                </div>
                                <p x-show="errors.password" x-text="errors.password" class="mt-2 text-sm text-red-600">
                                </p>



                                <!-- Password Requirements -->
                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                            :class="{
                                                'bg-green-500': passwordChecks.minLength,
                                                'bg-gray-200': !passwordChecks
                                                    .minLength
                                            }">
                                        </div>
                                        <span class="text-sm"
                                            :class="{
                                                'text-green-500': passwordChecks.minLength,
                                                'text-gray-500': !
                                                    passwordChecks.minLength
                                            }">
                                            Minimo 8 caratteri
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                            :class="{
                                                'bg-green-500': passwordChecks.uppercase,
                                                'bg-gray-200': !passwordChecks
                                                    .uppercase
                                            }">
                                        </div>
                                        <span class="text-sm"
                                            :class="{
                                                'text-green-500': passwordChecks.uppercase,
                                                'text-gray-500': !
                                                    passwordChecks.uppercase
                                            }">
                                            Almeno una maiuscola
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                            :class="{
                                                'bg-green-500': passwordChecks.lowercase,
                                                'bg-gray-200': !passwordChecks
                                                    .lowercase
                                            }">
                                        </div>
                                        <span class="text-sm"
                                            :class="{
                                                'text-green-500': passwordChecks.lowercase,
                                                'text-gray-500': !
                                                    passwordChecks.lowercase
                                            }">
                                            Almeno una minuscola
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                            :class="{
                                                'bg-green-500': passwordChecks.number,
                                                'bg-gray-200': !passwordChecks
                                                    .number
                                            }">
                                        </div>
                                        <span class="text-sm"
                                            :class="{
                                                'text-green-500': passwordChecks.number,
                                                'text-gray-500': !passwordChecks
                                                    .number
                                            }">
                                            Almeno un numero
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                            :class="{
                                                'bg-green-500': passwordChecks.special,
                                                'bg-gray-200': !passwordChecks
                                                    .special
                                            }">
                                        </div>
                                        <span class="text-sm"
                                            :class="{
                                                'text-green-500': passwordChecks.special,
                                                'text-gray-500': !passwordChecks
                                                    .special
                                            }">
                                            Almeno un carattere speciale
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                    Conferma Password <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input id="password_confirmation" :type="showPasswordConfirm ? 'text' : 'password'"
                                        x-model="formData.password_confirmation"
                                        class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                        :class="{ 'border-red-300': !passwordsMatch && formData.password_confirmation }"
                                        placeholder="Conferma la password" />
                                    <button type="button" @click="showPasswordConfirm = !showPasswordConfirm"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <i data-lucide="eye" x-show="!showPasswordConfirm"
                                            class="h-5 w-5 text-gray-400"></i>
                                        <i data-lucide="eye-off" x-show="showPasswordConfirm"
                                            class="h-5 w-5 text-gray-400"></i>
                                    </button>
                                </div>
                                <p x-show="!passwordsMatch && formData.password_confirmation"
                                    class="mt-2 text-sm text-red-600">
                                    Le password non coincidono
                                </p>
                            </div>

                            <!-- Aggiungi questa sezione dentro lo Step 6: Credenziali, prima del bottone di submit -->
                            <div class="mt-6">
                                <div class="relative flex items-start">
                                    <div class="flex h-5 items-center">
                                        <input id="privacy" name="privacy" type="checkbox"
                                            x-model="formData.privacy_accepted"
                                            class="h-4 w-4 rounded border-gray-300 text-custom-activeItem focus:ring-custom-activeItem">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="privacy" class="font-medium text-gray-700">Accetto l'informativa
                                            sulla privacy <span class="text-red-500">*</span></label>
                                        <p class="text-gray-500">
                                            Dichiaro di aver letto e compreso l'informativa sulla privacy ai sensi dell'art.
                                            13 del Regolamento UE 2016/679 (GDPR)
                                            e acconsento al trattamento dei miei dati personali per le finalità indicate
                                            nell'informativa.
                                            <a href="/privacy-policy"
                                                class="text-custom-activeItem hover:text-custom-activeItem/90"
                                                target="_blank">
                                                Leggi l'informativa completa
                                            </a>
                                        </p>
                                    </div>
                                </div>
                                <p x-show="errors.privacy" x-text="errors.privacy" class="mt-2 text-sm text-red-600"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Pulsanti di navigazione -->
                    <div class="mt-8 space-y-4 sm:flex sm:space-y-0 sm:space-x-4">
                        <button type="button" @click="prevStep"
                            class="w-full sm:w-1/2 flex justify-center items-center px-4 py-2 border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                            <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                            <span x-text="currentStep === 1 ? 'Torna al login' : 'Indietro'"></span>
                        </button>

                        <button x-show="!isLastStep" type="button" @click="nextStep" :disabled="!isFormValid"
                            class="w-full sm:w-1/2 flex justify-center items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem disabled:opacity-50 disabled:cursor-not-allowed">
                            <span>Avanti</span>
                            <i data-lucide="arrow-right" class="w-5 h-5 ml-2"></i>
                        </button>

                        <button x-show="isLastStep" type="submit" :disabled="!isFormValid || isLoading"
                            class="w-full sm:w-1/2 flex justify-center items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!isLoading">
                                <span>Completa Registrazione</span>
                            </template>
                            <template x-if="isLoading">
                                <div class="flex items-center">
                                    <i data-lucide="loader-2" class="animate-spin h-5 w-5 mr-2"></i>
                                    <span>Registrazione in corso...</span>
                                </div>
                            </template>
                        </button>
                    </div>

                    <!-- Error Message Generale -->
                    <div x-show="errors.general" class="mt-4 p-4 rounded-xl bg-red-50 border border-gray-200">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800" x-text="errors.general"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Login Link -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Hai già un account?
                            <a href="{{ route('login') }}"
                                class="font-medium text-custom-activeItem hover:text-custom-activeItem/90">
                                Accedi
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
    <script src="{{ asset('js/register.js') }}"></script>
@endpush
