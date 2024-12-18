@extends('layouts.dashboard')

@section('title', 'Modifica Publisher')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header con breadcrumb -->
    <div class="mb-6">
        <nav class="sm:hidden" aria-label="Back">
            <a href="{{ route('publishers.index') }}"
                class="flex items-center text-md font-medium text-gray-500 hover:text-gray-700">
                <i data-lucide="chevron-left" class="flex-shrink-0 -ml-1 mr-1 h-5 w-5 text-gray-400"></i>
                Torna alla lista
            </a>
        </nav>
        <nav class="hidden sm:flex" aria-label="Breadcrumb">
            <ol role="list" class="flex items-center space-x-4">
                <li>
                    <div class="flex">
                        <a href="{{ route('publishers.index') }}"
                            class="text-md font-medium text-gray-500 hover:text-gray-700">
                            Gestione Publisher
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i data-lucide="chevron-right" class="flex-shrink-0 h-5 w-5 text-gray-400"></i>
                        <span class="ml-4 text-md font-medium text-gray-500">
                            Modifica Publisher
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
                {{ $publisher->company_name }}
            </h2>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-custom-card shadow-md rounded-xl">
        <div class="px-4 py-5 sm:p-6">
            <form action="{{ route('publishers.update', $publisher) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Status Switch -->
                <div class="mb-6 flex items-center justify-between">
                    <span class="flex-grow flex flex-col">
                        <span class="text-md font-medium text-gray-900">Stato Publisher</span>
                        <span class="text-md text-gray-500">Attiva o disattiva questo publisher</span>
                    </span>
                    <button type="button" x-data="{ active: {{ json_encode($publisher->is_active) }} }" @click="active = !active"
                        :class="active ? 'bg-custom-activeItem' : 'bg-gray-200'"
                        class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                        <span class="sr-only">Stato publisher</span>
                        <span :class="active ? 'translate-x-5' : 'translate-x-0'"
                            class="pointer-events-none relative inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200">
                        </span>
                        <input type="hidden" name="is_active" :value="active ? 1 : 0">
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Prima colonna -->
                    <div class="space-y-4">
                        <!-- Company Name -->
                        <div>
                            <label for="company_name" class="block text-md font-medium text-gray-700">
                                Nome Azienda <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="building" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="text" name="company_name" id="company_name"
                                    class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                    value="{{ old('company_name', $publisher->company_name) }}" required>
                            </div>
                            @error('company_name')
                                <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Legal Name -->
                        <div>
                            <label for="legal_name" class="block text-md font-medium text-gray-700">
                                Ragione Sociale <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="briefcase" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="text" name="legal_name" id="legal_name"
                                    class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                    value="{{ old('legal_name', $publisher->legal_name) }}" required>
                            </div>
                            @error('legal_name')
                                <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- VAT Number -->
                        <div>
                            <label for="vat_number" class="block text-md font-medium text-gray-700">
                                Partita IVA <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="file-text" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="text" name="vat_number" id="vat_number" maxlength="11"
                                    class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                    value="{{ old('vat_number', $publisher->vat_number) }}" required>
                            </div>
                            @error('vat_number')
                                <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- IBAN -->
                        <div>
                            <label for="iban" class="block text-md font-medium text-gray-700">
                                IBAN <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="credit-card" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="text" name="iban" id="iban" maxlength="27"
                                    class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md uppercase"
                                    value="{{ old('iban', $publisher->iban) }}" required>
                            </div>
                            @error('iban')
                                <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- SWIFT -->
                        <div>
                            <label for="swift" class="block text-md font-medium text-gray-700">
                                SWIFT <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="credit-card" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="text" name="swift" id="swift" maxlength="11"
                                    class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md uppercase"
                                    value="{{ old('swift', $publisher->swift) }}" required>
                            </div>
                            @error('swift')
                                <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Seconda colonna -->
                    <div class="space-y-4">
                        <!-- Address Group -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="state" class="block text-md font-medium text-gray-700">
                                    Stato <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="map-pin" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" name="state" id="state"
                                        class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        value="{{ old('state', $publisher->state) }}" required>
                                </div>
                                @error('state')
                                    <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="state_id" class="block text-md font-medium text-gray-700">
                                    Sigla Stato <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="map" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" name="state_id" id="state_id"
                                        class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        value="{{ old('state_id', $publisher->state_id) }}" required>
                                </div>
                                @error('state_id')
                                    <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="county" class="block text-md font-medium text-gray-700">
                                    Provincia <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="map-pin" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" name="county" id="county"
                                        class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        value="{{ old('county', $publisher->county) }}" required>
                                </div>
                                @error('county')
                                    <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="county_id" class="block text-md font-medium text-gray-700">
                                    Sigla Provincia <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="map" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" name="county_id" id="county_id"
                                        class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        value="{{ old('county_id', $publisher->county_id) }}" required>
                                </div>
                                @error('county_id')
                                    <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-md font-medium text-gray-700">
                                    Città <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="map-pin" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" name="city" id="city"
                                        class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        value="{{ old('city', $publisher->city) }}" required>
                                </div>
                                @error('city')
                                    <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="postal_code" class="block text-md font-medium text-gray-700">
                                    CAP <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" name="postal_code" id="postal_code" maxlength="5"
                                        class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                        value="{{ old('postal_code', $publisher->postal_code) }}" required>
                                </div>
                                @error('postal_code')
                                    <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="address" class="block text-md font-medium text-gray-700">
                                Indirizzo <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="credit-card" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="text" name="address" id="address" maxlength="11"
                                    class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md uppercase"
                                    value="{{ old('address', $publisher->address) }}" required>
                            </div>
                            @error('address')
                                <p class="mt-1 text-md text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('publishers.show', $publisher) }}"
                        class="inline-flex justify-center items-center px-4 py-2 border border-gray-200 shadow-sm text-md font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                        <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                        Annulla
                    </a>
                    <button type="submit"
                        class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-md font-medium rounded-xl text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                        Salva Modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validazione dei campi del form
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (field.value.trim() === '') {
                    isValid = false;
                    field.classList.add('border-red-300', 'ring-red-500');
                    
                    // Cerca il contenitore del campo
                    const fieldContainer = field.closest('div');
                    // Verifica se esiste già un messaggio di errore
                    const existingError = fieldContainer.querySelector('.text-red-600');
                    
                    if (!existingError) {
                        const errorText = document.createElement('p');
                        errorText.classList.add('text-md', 'text-red-600', 'mt-1');
                        errorText.innerText = 'Questo campo è obbligatorio';
                        fieldContainer.appendChild(errorText);
                    }
                } else {
                    field.classList.remove('border-red-300', 'ring-red-500');
                    // Rimuovi eventuali messaggi di errore
                    const fieldContainer = field.closest('div');
                    const existingError = fieldContainer.querySelector('.text-red-600');
                    if (existingError) {
                        existingError.remove();
                    }
                }
            });

            if (!isValid) {
                event.preventDefault();
            }
        });

        // Feedback visuale per i campi mentre l'utente digita
        const inputs = form.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('border-red-300', 'ring-red-500');
                } else {
                    this.classList.remove('border-red-300', 'ring-red-500');
                    // Rimuovi eventuali messaggi di errore
                    const fieldContainer = this.closest('div');
                    const existingError = fieldContainer.querySelector('.text-red-600');
                    if (existingError) {
                        existingError.remove();
                    }
                }
            });

            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.classList.remove('border-red-300', 'ring-red-500');
                    // Rimuovi eventuali messaggi di errore
                    const fieldContainer = this.closest('div');
                    const existingError = fieldContainer.querySelector('.text-red-600');
                    if (existingError) {
                        existingError.remove();
                    }
                }
            });
        });

        // Formatta automaticamente l'IBAN in maiuscolo
        const ibanInput = document.querySelector('#iban');
        if (ibanInput) {
            ibanInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }

        // Formatta automaticamente lo SWIFT in maiuscolo
        const swiftInput = document.querySelector('#swift');
        if (swiftInput) {
            swiftInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    }
});
</script>
@endpush