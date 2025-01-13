@extends('layouts.dashboard')

@section('title', 'Dettagli Publisher')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="publisherShow()">
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
                                {{ $publisher->company_name }}
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:tracking-tight">
                    {{ $publisher->company_name }}
                </h2>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mt-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'details'"
                    :class="{
                        'border-custom-activeItem text-custom-activeItem': activeTab === 'details',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'details'
                    }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-md">
                    Dati Aziendali
                </button>

                <button @click="activeTab = 'databases'"
                    :class="{
                        'border-custom-activeItem text-custom-activeItem': activeTab === 'databases',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'databases'
                    }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-md">
                    Database
                </button>

                <button @click="activeTab = 'users'"
                    :class="{
                        'border-custom-activeItem text-custom-activeItem': activeTab === 'users',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'users'
                    }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-md">
                    Utenti Associati
                </button>

                <button @click="activeTab = 'ax'"
                    :class="{
                        'border-custom-activeItem text-custom-activeItem': activeTab === 'ax',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'ax'
                    }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-md">
                    Dati AX
                </button>
            </nav>
        </div>
        <!-- Company Details Tab -->
        <div x-show="activeTab === 'details'" x-cloak class="mt-6">
            <div class="bg-custom-card shadow-md rounded-xl">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Informazioni Aziendali</h3>
                        @if ($canUpdate)
                            <a href="{{ route('publishers.edit', $publisher) }}"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                                <i data-lucide="edit" class="h-5 w-5 mr-2"></i>
                                Modifica
                            </a>
                        @endif
                    </div>

                    <!-- Company Details Grid -->
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
                            <dt class="text-md font-medium text-gray-500">Stato</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $publisher->state }}</dd>
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
                            <dt class="text-md font-medium text-gray-500">Indirizzo</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $publisher->address }}</dd>
                        </div>

                        @if ($canUpdate)
                            <div>
                                <dt class="text-md font-medium text-gray-500">IBAN</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->iban }}</dd>
                            </div>

                            <div>
                                <dt class="text-md font-medium text-gray-500">SWIFT</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ $publisher->swift }}</dd>
                            </div>
                        @endif

                        <div>
                            <dt class="text-md font-medium text-gray-500">Stato</dt>
                            <dd class="mt-1">
                                <span
                                    class="inline-flex rounded-xl px-2.5 py-0.5 text-sm font-medium {{ $publisher->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $publisher->is_active ? 'Attivo' : 'Non attivo' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
        <!-- Database Tab -->
        <div x-show="activeTab === 'databases'" x-cloak class="mt-6">
            <div class="bg-custom-card shadow-md rounded-xl">
                <div class="px-4 py-5 sm:p-6">
                    <!-- Header con bottone aggiungi -->
                    <div class="mb-6 flex justify-between items-center">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Database Gestiti</h3>
                        @if ($canManageSubPublishers)
                            <button @click="openAddDatabaseModal" type="button"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                Aggiungi Database
                            </button>
                        @endif
                    </div>

                    <!-- Database List -->
                    <div class="mt-6">
                        <ul role="list" class="divide-y divide-gray-200">
                            @foreach ($publisher->subPublishers as $subPublisher)
                                <li class="py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <span
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl 
                                            {{ $subPublisher->is_primary ? 'bg-green-100' : 'bg-custom-activeItem/10' }}">
                                                    <span
                                                        class="text-md font-medium leading-none 
                                                {{ $subPublisher->is_primary ? 'text-green-800' : 'text-custom-activeItem' }}">
                                                        {{ strtoupper(substr($subPublisher->display_name, 0, 2)) }}
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="flex items-center">
                                                    <h4 class="text-md font-medium text-gray-900">
                                                        {{ $subPublisher->display_name }}
                                                    </h4>
                                                    @if ($subPublisher->is_primary)
                                                        <span
                                                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-xl text-sm font-medium bg-green-100 text-green-800">
                                                            Principale
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-1">
                                                    <p class="text-md text-gray-500">
                                                        Gruppo fatturazione: {{ $subPublisher->invoice_group }}
                                                    </p>
                                                    @if ($subPublisher->ax_name)
                                                        <p class="text-md text-gray-500">
                                                            Nome AX: {{ $subPublisher->ax_name }}
                                                        </p>
                                                    @endif
                                                    @if ($subPublisher->channel_detail)
                                                        <p class="text-md text-gray-500">
                                                            Channel Details: {{ $subPublisher->channel_detail }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @if ($canManageSubPublishers)
                                            <div class="flex items-center space-x-2">
                                                <button @click="editDatabase({{ json_encode($subPublisher) }})"
                                                    class="text-custom-activeItem hover:text-custom-activeItem/90"
                                                    title="Modifica Database">
                                                    <i data-lucide="edit" class="h-5 w-5"></i>
                                                </button>
                                                @if (!$subPublisher->is_primary)
                                                    <button @click="deleteDatabase({{ json_encode($subPublisher) }})"
                                                        class="text-red-600 hover:text-red-700" title="Elimina Database">
                                                        <i data-lucide="trash-2" class="h-5 w-5"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    @if ($subPublisher->notes)
                                        <div class="mt-2">
                                            <p class="text-md text-gray-500 italic">{{ $subPublisher->notes }}</p>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- Users Tab -->
        <div x-show="activeTab === 'users'" x-cloak class="mt-6">
            <div class="bg-custom-card shadow-md rounded-xl">
                <div class="px-4 py-5 sm:p-6">
                    <!-- Header -->
                    <div class="mb-6 flex justify-between items-center">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                            Utenti Associati
                        </h3>
                    </div>

                    <!-- Users Table -->
                    <div class="flex flex-col">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                <div class="overflow-hidden border border-gray-200 rounded-xl">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                                    Nome
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                                    Email
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                                    Ruolo
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                                    Stati
                                                </th>
                                                <th scope="col" class="relative px-6 py-3">
                                                    <span class="sr-only">Azioni</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @forelse($users as $user)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-md font-medium text-gray-900">
                                                            {{ $user->first_name }} {{ $user->last_name }}
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-md text-gray-900">{{ $user->email }}</div>
                                                        <div class="text-sm mt-1">
                                                            <span
                                                                class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-sm font-medium {{ $user->email_verified_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                                {{ $user->email_verified_at ? 'Email Verificata' : 'Email Non Verificata' }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm mt-1">
                                                            <span
                                                                class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-sm font-medium {{ $user->email_verified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                                {{ $user->email_verified ? 'Validato Admin' : 'Non Validato' }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex flex-col space-y-1">
                                                            <span
                                                                class="inline-flex rounded-xl px-2.5 py-0.5 text-sm font-medium {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                                {{ $user->is_active ? 'Account Attivo' : 'Account Non Attivo' }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-md font-medium">
                                                        <div class="flex justify-end space-x-2">
                                                            @if ($canManageUsers)
                                                                <button @click="editUser('{{ $user->id }}')"
                                                                    class="text-custom-activeItem hover:text-custom-activeItem/90"
                                                                    title="Modifica utente">
                                                                    <i data-lucide="edit" class="h-5 w-5"></i>
                                                                </button>

                                                                <button @click="deleteUser({{ json_encode($user) }})"
                                                                    class="text-red-600 hover:text-red-700"
                                                                    title="Elimina utente">
                                                                    <i data-lucide="trash-2" class="h-5 w-5"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5"
                                                        class="px-6 py-4 whitespace-nowrap text-md text-gray-500 text-center">
                                                        Nessun utente associato
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- AX Data Tab -->
        <div x-show="activeTab === 'ax'" x-cloak class="mt-6">
            <div class="bg-custom-card shadow-md rounded-xl">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Dati AX Publisher</h3>
                        @if ($canUpdate)
                            <button @click="editAXData"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                                <i data-lucide="edit" class="h-5 w-5 mr-2"></i>
                                Modifica
                            </button>
                        @endif
                    </div>

                    <!-- AX Data Grid -->
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                        <div>
                            <dt class="text-md font-medium text-gray-500">VendAccount</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->ax_vend_account ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-md font-medium text-gray-500">VendName</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->ax_vend_id ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-md font-medium text-gray-500">VendGroup</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->vend_group ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-md font-medium text-gray-500">PartyType</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->party_type ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-md font-medium text-gray-500">TaxWithholdCalculate</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->tax_withhold_calculate ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-md font-medium text-gray-500">ItemId</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->item_id ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-md font-medium text-gray-500">VatNumber</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->ax_vat_number ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-md font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->email ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-md font-medium text-gray-500">CostProfitCenter</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->cost_profit_center ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="text-md font-medium text-gray-500">payment</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->payment ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-md font-medium text-gray-500">payment_mode</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->payment_mode ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-md font-medium text-gray-500">currency_code</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->currency_code ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-md font-medium text-gray-500">sales_tax_group</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->sales_tax_group ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-md font-medium text-gray-500">number_sequence_group_id</dt>
                            <dd class="mt-1 text-md text-gray-900">{{ $axData->number_sequence_group_id ?? 'N/A' }}</dd>
                        </div>
                        <div>



                    </dl>
                </div>
            </div>
        </div>
        <!-- Add/Edit Database Modal -->
        <div x-show="showEditDatabaseModal" class="fixed z-10 inset-0 overflow-y-auto" role="dialog" aria-modal="true"
            x-cloak>
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="showEditDatabaseModal = false"></div>

                <!-- Modal panel -->
                <div
                    class="inline-block align-middle bg-custom-card rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <form @submit.prevent="submitEditDatabase">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Modifica Database</h3>
                                <div class="mt-6 space-y-6">
                                    <!-- Display Name -->
                                    <div>
                                        <label for="edit_display_name" class="block text-md font-medium text-gray-700">
                                            Nome Database <span class="text-red-500">*</span>
                                        </label>
                                        <div class="mt-1 relative rounded-xl shadow-sm">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i data-lucide="database" class="h-5 w-5 text-gray-400"></i>
                                            </div>
                                            <input type="text" x-model="editingDatabase.display_name"
                                                id="edit_display_name"
                                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                                required>
                                        </div>
                                    </div>

                                    <!-- AX Name -->
                                    <div>
                                        <label for="edit_ax_name" class="block text-md font-medium text-gray-700">
                                            Nome AX <span class="text-red-500">*</span>
                                        </label>
                                        <div class="mt-1 relative rounded-xl shadow-sm">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i data-lucide="box" class="h-5 w-5 text-gray-400"></i>
                                            </div>
                                            <input type="text" x-model="editingDatabase.ax_name" id="edit_ax_name"
                                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        </div>
                                    </div>

                                    <!-- Channel Detail -->
                                    <div>
                                        <label for="edit_channel_detail" class="block text-md font-medium text-gray-700">
                                            Channel Details <span class="text-red-500">*</span>
                                        </label>
                                        <div class="mt-1 relative rounded-xl shadow-sm">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i data-lucide="database" class="h-5 w-5 text-gray-400"></i>
                                            </div>
                                            <input type="text" x-model="editingDatabase.channel_detail"
                                                id="edit_channel_detail"
                                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label for="edit_notes"
                                            class="block text-md font-medium text-gray-700">Note</label>
                                        <div class="mt-1 relative rounded-xl shadow-sm">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i data-lucide="file-text" class="h-5 w-5 text-gray-400"></i>
                                            </div>
                                            <textarea id="edit_notes" x-model="editingDatabase.notes" rows="3"
                                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                    </textarea>
                                        </div>
                                    </div>

                                    <!-- Is Primary Toggle -->
                                    <div class="flex items-center">
                                        <button type="button"
                                            class="relative inline-flex flex-shrink-0 h-6 transition-colors duration-200 ease-in-out border-2 border-transparent rounded-full cursor-pointer w-11 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem"
                                            :class="[editingDatabase.is_primary ? 'bg-custom-activeItem' : 'bg-gray-200']"
                                            @click="editingDatabase.is_primary = !editingDatabase.is_primary">
                                            <span
                                                class="relative inline-block w-5 h-5 transition duration-200 ease-in-out transform bg-white rounded-full shadow pointer-events-none"
                                                :class="[editingDatabase.is_primary ? 'translate-x-5' : 'translate-x-0']">
                                            </span>
                                        </button>
                                        <span class="ml-3 text-md font-medium text-gray-900">Database Principale</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="isSubmitting"
                                class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-custom-activeItem text-base font-medium text-white hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem sm:ml-3 sm:w-auto sm:text-md disabled:opacity-50">
                                <span x-show="!isSubmitting">Salva Modifiche</span>
                                <div x-show="isSubmitting" class="flex items-center">
                                    <i data-lucide="loader" class="animate-spin h-5 w-5 mr-2"></i>
                                    <span>Salvataggio...</span>
                                </div>
                            </button>
                            <button type="button" @click="showEditDatabaseModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-200 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem sm:mt-0 sm:w-auto sm:text-md">
                                Annulla
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit AX Data Modal -->
        <div x-show="showEditAXModal" class="fixed z-10 inset-0 overflow-y-auto" role="dialog" aria-modal="true"
            x-cloak>
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showEditAXModal = false">
                </div>

                <!-- Modal panel -->
                <div
                    class="inline-block align-middle bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                    <form @submit.prevent="submitEditAXData">
                        <div class="sm:flex sm:items-start">
                            <div class="w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-5">Modifica Dati AX</h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">

                                    <div>
                                        <label for="ax_vend_account"
                                            class="block text-md font-medium text-gray-700">VendAccount</label>
                                        <input type="text" x-model="editingAXData.ax_vend_account"
                                            id="ax_vend_account"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>

                                    <div>
                                        <label for="ax_vend_id"
                                            class="block text-md font-medium text-gray-700">VendName</label>
                                        <input type="text" x-model="editingAXData.ax_vend_id" id="ax_vend_id"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>

                                    <div>
                                        <label for="vend_group"
                                            class="block text-md font-medium text-gray-700">VendGroup</label>
                                        <input type="text" x-model="editingAXData.vend_group" id="vend_group"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>

                                    <div>
                                        <label for="party_type"
                                            class="block text-md font-medium text-gray-700">PartyType</label>
                                        <input type="text" x-model="editingAXData.party_type" id="party_type"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>

                                    <div>
                                        <label for="tax_withhold_calculate"
                                            class="block text-md font-medium text-gray-700">TaxWithholdCalculate</label>
                                        <input type="text" x-model="editingAXData.tax_withhold_calculate"
                                            id="tax_withhold_calculate"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>

                                    <div>
                                        <label for="item_id"
                                            class="block text-md font-medium text-gray-700">ItemId</label>
                                        <input type="text" x-model="editingAXData.item_id" id="item_id"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>

                                    <div>
                                        <label for="email"
                                            class="block text-md font-medium text-gray-700">Email</label>
                                        <input type="text" x-model="editingAXData.email" id="email"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>
                                    <div>
                                        <label for="ax_vat_number"
                                            class="block text-md font-medium text-gray-700">ax_vat_number</label>
                                        <input type="text" x-model="editingAXData.ax_vat_number" id="ax_vat_number"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>

                                    <div>
                                        <label for="cost_profit_center"
                                            class="block text-md font-medium text-gray-700">CostProfitCenter</label>
                                        <input type="text" x-model="editingAXData.cost_profit_center"
                                            id="cost_profit_center"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>


                                    <div>
                                        <label for="payment"
                                            class="block text-md font-medium text-gray-700">payment</label>
                                        <input type="text" x-model="editingAXData.payment" id="payment"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>
                                    <div>
                                        <label for="cost_profit_center"
                                            class="block text-md font-medium text-gray-700">payment_mode</label>
                                        <input type="text" x-model="editingAXData.payment_mode"
                                            id="cost_profit_center"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>
                                    <div>
                                        <label for="currency_code"
                                            class="block text-md font-medium text-gray-700">currency_code</label>
                                        <input type="text" x-model="editingAXData.currency_code" id="currency_code"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>
                                    <div>
                                        <label for="sales_tax_group"
                                            class="block text-md font-medium text-gray-700">sales_tax_group</label>
                                        <input type="text" x-model="editingAXData.sales_tax_group"
                                            id="sales_tax_group"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>
                                    <div>
                                        <label for="number_sequence_group_id"
                                            class="block text-md font-medium text-gray-700">number_sequence_group_id</label>
                                        <input type="text" x-model="editingAXData.number_sequence_group_id"
                                            id="number_sequence_group_id"
                                            class="mt-1 block w-full border border-gray-300 rounded-xl shadow-sm py-2 px-3 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        <p class="mt-1 text-sm text-gray-500">
                                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                                            incididunt ut labore.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="isSubmitting"
                                class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-custom-activeItem text-base font-medium text-white hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem sm:ml-3 sm:w-auto sm:text-md disabled:opacity-50">
                                <span x-show="!isSubmitting">Salva Modifiche</span>
                                <div x-show="isSubmitting" class="flex items-center">
                                    <i data-lucide="loader" class="animate-spin h-5 w-5 mr-2"></i>
                                    <span>Salvataggio...</span>
                                </div>
                            </button>
                            <button type="button" @click="showEditAXModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-200 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem sm:mt-0 sm:w-auto sm:text-md">
                                Annulla
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Database Modal -->
        <div x-show="showDeleteDatabaseModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    @click="showDeleteDatabaseModal = false"></div>

                <div
                    class="relative inline-block align-middle bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="alert-triangle" class="h-6 w-6 text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Elimina Database</h3>
                            <div class="mt-2">
                                <p class="text-md text-gray-500">
                                    Sei sicuro di voler eliminare questo database? Questa azione non può essere annullata.
                                </p>
                                <p class="mt-2 text-md font-medium text-gray-900" x-show="deletingDatabase">
                                    Database: <span x-text="deletingDatabase?.display_name"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="confirmDeleteDatabase" :disabled="isSubmitting"
                            class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-md disabled:opacity-50">
                            Elimina
                        </button>
                        <button type="button" @click="showDeleteDatabaseModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem sm:mt-0 sm:w-auto sm:text-md">
                            Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete User Modal -->
        <div x-show="showDeleteUserModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    @click="showDeleteUserModal = false"></div>

                <div
                    class="relative inline-block align-middle bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="alert-triangle" class="h-6 w-6 text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Conferma eliminazione</h3>
                            <div class="mt-2">
                                <p class="text-md text-gray-500">
                                    Sei sicuro di voler eliminare questo utente? Questa azione non può essere annullata.
                                </p>
                                <p class="mt-2 text-md font-medium text-gray-900" x-show="deletingUser">
                                    Utente: <span x-text="deletingUser?.first_name + ' ' + deletingUser?.last_name"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="confirmDeleteUser()" :disabled="isSubmitting"
                            class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-md disabled:opacity-50">
                            <span x-show="!isSubmitting">Elimina</span>
                            <span x-show="isSubmitting" class="flex items-center">
                                <i data-lucide="loader" class="animate-spin h-5 w-5 mr-2"></i>
                                Eliminazione in corso...
                            </span>
                        </button>
                        <button type="button" @click="showDeleteUserModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem sm:mt-0 sm:w-auto sm:text-md">
                            Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Database Modal -->
        <div x-show="showAddDatabaseModal" class="fixed z-10 inset-0 overflow-y-auto" role="dialog" aria-modal="true"
            x-cloak>
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="showAddDatabaseModal = false"></div>

                <!-- Modal panel -->
                <div
                    class="inline-block align-middle bg-custom-card rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <form @submit.prevent="submitNewDatabase">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Aggiungi Database</h3>
                                <div class="mt-6 space-y-6">
                                    <!-- Display Name -->
                                    <div>
                                        <label for="display_name" class="block text-md font-medium text-gray-700">
                                            Nome Database <span class="text-red-500">*</span>
                                        </label>
                                        <div class="mt-1 relative rounded-xl shadow-sm">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i data-lucide="database" class="h-5 w-5 text-gray-400"></i>
                                            </div>
                                            <input type="text" x-model="newDatabase.display_name" id="display_name"
                                                required
                                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        </div>
                                    </div>

                                    <!-- AX Name -->
                                    <div>
                                        <label for="ax_name" class="block text-md font-medium text-gray-700">
                                            Nome AX <span class="text-red-500">*</span>
                                        </label>
                                        <div class="mt-1 relative rounded-xl shadow-sm">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i data-lucide="box" class="h-5 w-5 text-gray-400"></i>
                                            </div>
                                            <input type="text" x-model="newDatabase.ax_name" id="ax_name" required
                                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                        </div>
                                    </div>

                                    <!-- Channel Detail -->
                                    <div>
                                        <label for="channel_detail" class="block text-md font-medium text-gray-700">
                                            Channel Details <span class="text-red-500">*</span>
                                        </label>
                                        <div class="mt-1 relative rounded-xl shadow-sm">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i data-lucide="database" class="h-5 w-5 text-gray-400"></i>
                                            </div>
                                            <input type="text" x-model="newDatabase.channel_detail"
                                                id="channel_detail" required
                                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                            <p class="mt-1 text-sm text-gray-500">
                                                Per i nuovi utenti inserire il nome del database/editore.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label for="notes" class="block text-md font-medium text-gray-700">Note</label>
                                        <div class="mt-1 relative rounded-xl shadow-sm">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i data-lucide="file-text" class="h-5 w-5 text-gray-400"></i>
                                            </div>
                                            <textarea id="notes" x-model="newDatabase.notes" rows="3"
                                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md">
                                    </textarea>
                                        </div>
                                    </div>

                                    <!-- Is Primary Toggle -->
                                    <div class="flex items-center">
                                        <button type="button"
                                            class="relative inline-flex flex-shrink-0 h-6 transition-colors duration-200 ease-in-out border-2 border-transparent rounded-full cursor-pointer w-11 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem"
                                            :class="[newDatabase.is_primary ? 'bg-custom-activeItem' : 'bg-gray-200']"
                                            @click="newDatabase.is_primary = !newDatabase.is_primary">
                                            <span
                                                class="relative inline-block w-5 h-5 transition duration-200 ease-in-out transform bg-white rounded-full shadow pointer-events-none"
                                                :class="[newDatabase.is_primary ? 'translate-x-5' : 'translate-x-0']">
                                            </span>
                                        </button>
                                        <span class="ml-3 text-md font-medium text-gray-900">Database Principale</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="isSubmitting"
                                class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-custom-activeItem text-base font-medium text-white hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem sm:ml-3 sm:w-auto sm:text-md disabled:opacity-50">
                                <span x-show="!isSubmitting">Salva</span>
                                <div x-show="isSubmitting" class="flex items-center">
                                    <i data-lucide="loader" class="animate-spin h-5 w-5 mr-2"></i>
                                    <span>Salvataggio...</span>
                                </div>
                            </button>
                            <button type="button" @click="showAddDatabaseModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-200 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem sm:mt-0 sm:w-auto sm:text-md">
                                Annulla
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
        function publisherShow() {
            return {
                activeTab: 'details',
                showAddDatabaseModal: false,
                showEditDatabaseModal: false,
                showDeleteDatabaseModal: false,
                showDeleteUserModal: false,
                showEditAXModal: false,
                userIdToDelete: null,
                isSubmitting: false,
                databases: @json($publisher->subPublishers),
                newDatabase: {
                    display_name: '',
                    invoice_group: '{{ $publisher->legal_name }}',
                    notes: '',
                    is_primary: false,
                    ax_name: '',
                    channel_detail: ''
                },
                editingDatabase: null,
                deletingDatabase: null,
                deletingUser: null,
                editingAXData: null,
                axData: @json($axData ?? null),
                errors: {},

                async init() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const tab = urlParams.get('tab');
                    if (tab) {
                        this.activeTab = tab;
                    }


                    // Inizializza editingDatabase con valori di default
                    this.editingDatabase = {
                        display_name: '',
                        ax_name: '',
                        channel_detail: '',
                        notes: '',
                        is_primary: false
                    };

                    // Inizializza i dati AX se disponibili
                    if (this.axData) {
                        this.editingAXData = {
                            ...this.axData
                        };
                    } else {
                        this.editingAXData = {
                            period: '',
                            vend_account: '',
                            purch_id: '',
                            line_number: '',
                            vend_name: '{{ $publisher->legal_name }}',
                            site_url: '',
                            address: '',
                            street: '',
                            zip_code: '',
                            city: '',
                            state: '',
                            country_region_id: '',
                            vend_group: '',
                            party_type: '',
                            vat_num: '{{ $publisher->vat_number }}',
                            fiscal_code: '',
                            payment: '',
                            paym_mode: '',
                            tax_withhold_calculate: '',
                            item_id: '',
                            email: '{{ $publisher->ax_email }}',
                            bank_iban: '{{ $publisher->iban }}',
                            cost_profit_center: '',
                            channel_detail: ''
                        };
                    }
                },

                // Database Methods
                openAddDatabaseModal() {
                    this.newDatabase = {
                        display_name: '',
                        invoice_group: '{{ $publisher->legal_name }}',
                        notes: '',
                        is_primary: true,
                        ax_name: '',
                        channel_detail: ''
                    };
                    this.showAddDatabaseModal = true;
                },

                editDatabase(database) {
                    this.editingDatabase = {
                        ...database
                    };
                    this.showEditDatabaseModal = true;
                },

                deleteDatabase(database) {
                    this.deletingDatabase = database;
                    this.showDeleteDatabaseModal = true;
                },

                async submitEditDatabase() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;
                    this.errors = {};

                    try {
                        const response = await fetch(
                            `/publishers/{{ $publisher->id }}/databases/${this.editingDatabase.id}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(this.editingDatabase)
                            }
                        );

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Si è verificato un errore');
                        }

                        this.$dispatch('notify', {
                            type: 'success',
                            message: 'Database aggiornato con successo'
                        });

                        window.location.href = `${window.location.pathname}?tab=databases`;
                    } catch (error) {
                        this.$dispatch('notify', {
                            type: 'error',
                            message: error.message
                        });
                    } finally {
                        this.isSubmitting = false;
                        this.showEditDatabaseModal = false;
                    }
                },

                async submitNewDatabase() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;
                    this.errors = {};

                    try {
                        const response = await fetch(`/publishers/{{ $publisher->id }}/databases`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.newDatabase)
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Si è verificato un errore');
                        }

                        this.$dispatch('notify', {
                            type: 'success',
                            message: 'Database aggiunto con successo'
                        });

                        window.location.href = `${window.location.pathname}?tab=databases`;
                    } catch (error) {
                        this.$dispatch('notify', {
                            type: 'error',
                            message: error.message
                        });
                    } finally {
                        this.isSubmitting = false;
                        this.showAddDatabaseModal = false;
                    }
                },

                async confirmDeleteDatabase() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;

                    try {
                        const response = await fetch(
                            `/publishers/{{ $publisher->id }}/databases/${this.deletingDatabase.id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content'),
                                    'Accept': 'application/json'
                                }
                            });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || 'Si è verificato un errore durante la cancellazione.');
                        }

                        this.$dispatch('notify', {
                            type: 'success',
                            message: 'Database eliminato con successo'
                        });

                        window.location.href = `${window.location.pathname}?tab=databases`;
                    } catch (error) {
                        this.$dispatch('notify', {
                            type: 'error',
                            message: error.message
                        });
                    } finally {
                        this.isSubmitting = false;
                        this.showDeleteDatabaseModal = false;
                    }
                },

                // AX Data Methods
                editAXData() {
                    if (!this.editingAXData) {
                        this.editingAXData = {
                            ax_vend_account: '',
                            ax_vend_id: '',
                            vend_group: '',
                            party_type: '',
                            tax_withhold_calculate: '',
                            item_id: '',
                            ax_vat_number: '',
                            email: '',
                            cost_profit_center: '',
                            payment: '',
                            payment_mode: '',
                            currency_code: '',
                            sales_tax_group: '',
                            number_sequence_group_id: ''
                        };
                    }
                    this.showEditAXModal = true;
                },

                async submitEditAXData() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;

                    try {
                        const response = await fetch(`/publishers/{{ $publisher->id }}/ax-data`, {
                            method: this.axData ? 'PUT' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.editingAXData)
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Si è verificato un errore');
                        }

                        this.$dispatch('notify', {
                            type: 'success',
                            message: 'Dati AX aggiornati con successo'
                        });

                        window.location.href = `${window.location.pathname}?tab=ax`;
                    } catch (error) {
                        this.$dispatch('notify', {
                            type: 'error',
                            message: error.message
                        });
                    } finally {
                        this.isSubmitting = false;
                        this.showEditAXModal = false;
                    }
                },

                // User Methods
                editUser(userId) {
                    window.location.href = `/users/${userId}/edit`;
                },

                deleteUser(user) {
                    this.userIdToDelete = user.id;
                    this.deletingUser = user;
                    this.showDeleteUserModal = true;
                },

                async confirmDeleteUser() {
                    if (!this.userIdToDelete || this.isSubmitting) return;
                    this.isSubmitting = true;

                    try {
                        const response = await fetch(`/users/${this.userIdToDelete}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || 'Si è verificato un errore durante la cancellazione.');
                        }

                        this.$dispatch('notify', {
                            type: 'success',
                            message: 'Utente eliminato con successo'
                        });

                        window.location.href = `${window.location.pathname}?tab=users`;
                    } catch (error) {
                        this.$dispatch('notify', {
                            type: 'error',
                            message: error.message
                        });
                    } finally {
                        this.isSubmitting = false;
                        this.showDeleteUserModal = false;
                    }
                }
            };
        }
    </script>
@endpush
