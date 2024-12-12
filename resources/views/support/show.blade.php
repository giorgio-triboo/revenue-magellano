@extends('layouts.dashboard')

@section('title', 'Supporto')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" x-data="supportForm()">
            <!-- Alert Banner -->
            <div
                x-show="notifications.show"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform -translate-y-2"
                class="mb-6 rounded-xl p-4 border"
                :class="{
                    'bg-green-50 border-green-200': notifications.type === 'success',
                    'bg-red-50 border-red-200': notifications.type === 'error'
                }"
            >
                <div class="flex">
                    <div class="flex-shrink-0" x-show="notifications.type === 'success'">
                        <i data-lucide="check-circle" class="h-5 w-5 text-green-400"></i>
                    </div>
                    <div class="flex-shrink-0" x-show="notifications.type === 'error'">
                        <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-md font-medium"
                            :class="notifications.type === 'success' ? 'text-green-800' : 'text-red-800'">
                            <span x-text="notifications.type === 'success' ? 'Operazione completata' : 'Errore'"></span>
                        </h3>
                        <div class="mt-2 text-md"
                            :class="notifications.type === 'success' ? 'text-green-700' : 'text-red-700'"
                            x-text="notifications.message">
                        </div>
                    </div>
                    <div class="ml-auto pl-3">
                        <div class="-mx-1.5 -my-1.5">
                            <button @click="notifications.show = false" type="button"
                                class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="notifications.type === 'success' 
                                    ? 'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-green-600 focus:ring-offset-green-50'
                                    : 'bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600 focus:ring-offset-red-50'">
                                <span class="sr-only">Chiudi</span>
                                <i data-lucide="x" class="h-5 w-5"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-custom-card overflow-hidden shadow-sm sm:rounded-xl">
                <div class="p-6">
                    <h2 class="text-2xl font-bold mb-6">Richiedi Supporto</h2>

                    <form @submit.prevent="submitForm" class="space-y-6">
                        @csrf

                        <!-- Categoria -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">
                                Categoria <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="folder" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <select id="category" name="category" required
                                    class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm">
                                    <option value="">Seleziona categoria</option>
                                    <option value="technical">Assistenza Tecnica</option>
                                    <option value="billing">Assistenza Fatturazione</option>
                                </select>
                            </div>
                        </div>

                        <!-- Oggetto -->
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700">
                                Oggetto <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="text" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input type="text" id="subject" name="subject" required
                                    class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                    placeholder="Inserisci l'oggetto della richiesta">
                            </div>
                        </div>

                        <!-- Descrizione -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">
                                Descrizione <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <textarea id="description" name="description" rows="4" required
                                    class="appearance-none block w-full px-3 py-2 border border-gray-200 rounded-xl shadow-sm focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                    placeholder="Descrivi il problema o la richiesta"></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit"
                                class="inline-flex justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                                Invia Richiesta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function supportForm() {
    return {
        notifications: {
            show: false,
            type: null,
            message: ''
        },

        showNotification(type, message) {
            this.notifications = {
                show: true,
                type,
                message
            };
            setTimeout(() => {
                this.notifications.show = false;
            }, 5000);

            // Scroll verso l'alto per assicurarsi che il banner sia visibile
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async submitForm(e) {
            const formData = {
                category: e.target.category.value,
                subject: e.target.subject.value,
                description: e.target.description.value
            };

            try {
                const response = await fetch('{{ route('support.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('success', data.message);
                    e.target.reset();
                } else {
                    this.showNotification('error', data.message || 'Si è verificato un errore durante l\'invio della richiesta');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showNotification('error', 'Si è verificato un errore durante l\'invio della richiesta');
            }
        },

        init() {
            // Se c'è un messaggio di successo nella sessione, mostralo
            @if (session('success'))
                this.showNotification('success', '{{ session('success') }}');
            @endif

            // Se ci sono errori di validazione Laravel, mostra la notifica
            @if ($errors->any())
                this.showNotification('error', 'Si sono verificati degli errori. Controlla i campi del form.');
            @endif
        }
    }
}
</script>
@endpush