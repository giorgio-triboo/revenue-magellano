@extends('layouts.dashboard')

@section('title', 'Upload Consuntivi')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-cloak x-data="uploadManager()">
        <!-- Header principale -->
        <div class="md:flex md:items-center md:justify-between mb-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    Upload Consuntivi
                </h2>
            </div>
            <div class="flex justify-between items-center mb-4">
                <a href="{{ route('uploads.template') }}"
                    class="inline-flex justify-end items-center px-4 py-2 rounded-xl bg-custom-btn text-white hover:bg-custom-btn">
                    <i data-lucide="download" class="h-5 w-5 mr-2"></i>
                    Scarica Template
                </a>
            </div>
        </div>

        <!-- Cards Container -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Card Selezione Periodo -->
            <div class="bg-custom-card shadow-md rounded-xl">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Nuovo Upload</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Anno -->
                        <div>
                            <label for="year" class="block text-md font-medium text-gray-700">Anno</label>
                            <select x-model="form.year" id="year"
                                class="mt-1 block w-full pl-3 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm text-base focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                required>
                                <option value="" disabled selected>Seleziona anno</option>
                                <template x-for="year in years" :key="year">
                                    <option :value="year" x-text="year"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Mese -->
                        <div>
                            <label for="month" class="block text-md font-medium text-gray-700">Mese</label>
                            <select x-model="form.month" id="month"
                                class="mt-1 block w-full pl-3 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm text-base focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-md"
                                required>
                                <option value="" disabled selected>Seleziona mese</option>
                                <template x-for="(month, index) in months" :key="index">
                                    <option :value="index + 1" x-text="month"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>




            <!-- Card Upload File -->
            <div class="bg-custom-card shadow-md rounded-xl p-6">
                <div class="space-y-6">
                    <!-- Header della card -->
                    <div>
                        <h3 class="text-base font-medium text-gray-900">Carica File</h3>
                        <p class="mt-1 text-md text-gray-500">Seleziona un file CSV da caricare nel sistema</p>
                    </div>

                    <!-- Area Upload -->
                    <div class="space-y-4">
                        <div class="relative">
                            <input type="file" @change="handleFileChange" accept=".csv"
                                class="block w-full text-md text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-md file:font-semibold file:bg-custom-activeItem/10 file:text-custom-activeItem hover:file:bg-custom-activeItem/20 focus:outline-none"
                                :disabled="isUploading" required>
                        </div>

                        <!-- Upload Button -->
                        <div class="flex justify-end" x-cloak>
                            <button type="button" @click="uploadFile" :disabled="!selectedFile || isUploading"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl text-md font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem disabled:opacity-50 disabled:cursor-not-allowed">
                                <i data-lucide="upload" class="h-4 w-4 mr-2" x-show="!isUploading"></i>
                                <i data-lucide="loader-2" class="h-4 w-4 mr-2 animate-spin" x-show="isUploading"></i>
                                <span x-text="isUploading ? 'Caricamento...' : 'Carica File'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Alert Banner -->
        <div x-show="notifications.show" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2" class="mb-6 rounded-xl p-4 border"
            :class="{
                'bg-green-50 border-green-200': notifications.type === 'success',
                'bg-red-50 border-red-200': notifications.type === 'error'
            }">
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
                    <div class="mt-2 text-md" :class="notifications.type === 'success' ? 'text-green-700' : 'text-red-700'"
                        x-text="notifications.message">
                    </div>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button @click="notifications.show = false" type="button"
                            class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :class="notifications.type === 'success' ?
                                'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-green-600 focus:ring-offset-green-50' :
                                'bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600 focus:ring-offset-red-50'">
                            <span class="sr-only">Chiudi</span>
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabella Upload -->
        <div class="bg-custom-card shadow-md rounded-xl">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Cronologia Upload</h3>

                <div class="flex flex-col">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <div class="overflow-hidden border border-gray-200 rounded-xl">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Anno
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Mese
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Stato CSV
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Stato AX
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Stato SFTP
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Record Elaborati
                                            </th>
                                            <th scope="col" class="relative px-6 py-3">
                                                <span class="sr-only">Azioni</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse ($uploads as $upload)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-md text-gray-900">{{ $upload->process_date->year }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-md text-gray-900">
                                                        {{ __($upload->process_date->locale('it')->monthName) }}
                                                    </div>
                                                </td>
                                                <!-- Stato CSV -->
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-medium rounded-xl"
                                                        :class="getStatusClass('{{ $upload->status }}')">
                                                        <span x-text="getStatusText('{{ $upload->status }}')"></span>
                                                    </span>
                                                    @if ($upload->status === 'processing')
                                                        <div class="mt-1">
                                                            <div class="text-xs text-gray-500">
                                                                {{ round($upload->progress_percentage ?? 0) }}%
                                                            </div>
                                                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                                <div class="bg-custom-activeItem h-1.5 rounded-full"
                                                                    style="width: {{ $upload->progress_percentage ?? 0 }}%">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>
                                                <!-- Stato Export AX -->
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-medium rounded-xl"
                                                        :class="getAxExportStatusClass(@js($upload))">
                                                        <span x-text="getAxExportStatusText(@js($upload))"></span>
                                                    </span>
                                                </td>
                                                <!-- Stato SFTP -->
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-medium rounded-xl"
                                                        :class="getSftpStatusClass(@js($upload))">
                                                        <span x-text="getSftpStatusText(@js($upload))"></span>
                                                    </span>
                                                </td>
                                                <!-- Record Elaborati -->
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-md text-gray-500">
                                                        @if ($upload->status !== 'pending')
                                                            @if ($upload->processed_records !== null)
                                                                {{ $upload->processed_records }} /
                                                                {{ $upload->total_records }}
                                                            @else
                                                                -
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                                <!-- Azioni -->
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-md font-medium">
                                                    <div class="flex items-center justify-end space-x-3">
                                                        <!-- Pulsante AX -->
                                                        <button type="button" @click="openExportModal(@js($upload))"
                                                            @if (!$upload->ax_export_path || $upload->status !== 'completed') disabled @endif
                                                            class="inline-flex items-center px-3 py-1 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem disabled:opacity-50">
                                                            AX
                                                        </button>
                                                        <!-- Pulsante Pubblica -->
                                                        <button type="button" @click="confirmPublish(@js($upload))"
                                                            @if ($upload->status !== 'completed') disabled @endif
                                                            class="inline-flex items-center px-3 py-1 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem disabled:opacity-50">
                                                            Pubblica
                                                        </button>
                                                        <!-- Pulsante Email -->
                                                        <button type="button"
                                                            @click="confirmSendEmail(@js($upload))"
                                                            @if ($upload->status !== 'published' || $upload->notification_sent_at !== null) disabled @endif
                                                            class="inline-flex items-center px-3 py-1 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem disabled:opacity-50">
                                                            Email
                                                        </button>
                                                        <!-- Pulsante Info -->
                                                        <button type="button" @click="showInfo(@js($upload))"
                                                            class="inline-flex items-center px-3 py-1 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                                                            Info
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7"
                                                    class="px-6 py-4 whitespace-nowrap text-md text-gray-500 text-center">
                                                    Nessun upload presente
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $uploads->links() }}
                    </div>
                </div>
            </div>
        </div>


        <!-- Modale Pubblicazione -->
        <div x-show="showPublishModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPublishModal = false"
                    aria-hidden="true"></div>

                <div
                    class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="calendar-days" class="h-6 w-6 text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Conferma Pubblicazione</h3>
                            <div class="mt-2">
                                <p class="text-md text-gray-500">Sei sicuro di voler pubblicare questo file?</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="publishUpload()"
                            class="inline-flex w-full justify-center rounded-xl bg-custom-activeItem px-3 py-2 text-md font-semibold text-white shadow-sm hover:bg-custom-activeItem/90 sm:ml-3 sm:w-auto">
                            Pubblica
                        </button>
                        <button type="button" @click="showPublishModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-md font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale Email (modifica in index.blade.php) -->
        <div x-show="showEmailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showEmailModal = false"
                    aria-hidden="true"></div>

                <div
                    class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="mail" class="h-6 w-6 text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Notifica Pubblicazione</h3>
                            <div class="mt-2">
                                <p class="text-md text-gray-500">Scegli come procedere con l'invio della notifica:</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-col space-y-3">
                        <button type="button" @click="sendTestEmail()"
                            class="inline-flex w-full justify-center items-center rounded-xl bg-blue-100 px-3 py-2 text-md font-semibold text-blue-600 shadow-sm hover:bg-blue-200">
                            <i data-lucide="send-test" class="h-5 w-5 mr-2"></i>
                            Invia Email di Test agli Admin
                        </button>
                        <button type="button" @click="sendEmail()"
                            class="inline-flex w-full justify-center rounded-xl bg-custom-activeItem px-3 py-2 text-md font-semibold text-white shadow-sm hover:bg-custom-activeItem/90">
                            <i data-lucide="send" class="h-5 w-5 mr-2"></i>
                            Invia Email ai Publisher
                        </button>
                        <button type="button" @click="showEmailModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-md font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale Info -->
        <div x-show="showInfoModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeInfoModal"
                    aria-hidden="true"></div>

                <div
                    class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Informazioni Upload</h3>

                    <dl class="divide-y divide-gray-200">
                        <!-- Status CSV -->
                        <div class="py-3 grid grid-cols-3">
                            <dt class="text-md font-medium text-gray-500">Status CSV</dt>
                            <dd class="text-md text-gray-900 col-span-2">
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-medium rounded-xl"
                                    :class="getStatusClass(currentInfoUpload?.status)">
                                    <span x-text="getStatusText(currentInfoUpload?.status)"></span>
                                </span>
                            </dd>
                        </div>

                        <!-- Status Export AX -->
                        <div class="py-3 grid grid-cols-3">
                            <dt class="text-md font-medium text-gray-500">Status Export AX</dt>
                            <dd class="text-md text-gray-900 col-span-2">
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-medium rounded-xl"
                                    :class="getAxExportStatusClass(currentInfoUpload)">
                                    <span x-text="getAxExportStatusText(currentInfoUpload)"></span>
                                </span>
                            </dd>
                        </div>

                        <!-- Status SFTP -->
                        <div class="py-3 grid grid-cols-3">
                            <dt class="text-md font-medium text-gray-500">Status SFTP</dt>
                            <dd class="text-md text-gray-900 col-span-2">
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-medium rounded-xl"
                                    :class="getSftpStatusClass(currentInfoUpload)">
                                    <span x-text="getSftpStatusText(currentInfoUpload)"></span>
                                </span>
                            </dd>
                        </div>

                        <!-- Data Upload -->
                        <div class="py-3 grid grid-cols-3">
                            <dt class="text-md font-medium text-gray-500">Data Upload</dt>
                            <dd class="text-md text-gray-900 col-span-2"
                                x-text="formatDateTime(currentInfoUpload?.created_at)"></dd>
                        </div>

                        <!-- Data Pubblicazione -->
                        <div class="py-3 grid grid-cols-3">
                            <dt class="text-md font-medium text-gray-500">Data Pubblicazione</dt>
                            <dd class="text-md text-gray-900 col-span-2"
                                x-text="currentInfoUpload?.published_at ? formatDateTime(currentInfoUpload?.published_at) : 'Non pubblicato'">
                            </dd>
                        </div>

                        <!-- Data Upload SFTP -->
                        <div x-show="currentInfoUpload?.sftp_uploaded_at" class="py-3 grid grid-cols-3">
                            <dt class="text-md font-medium text-gray-500">Data Upload SFTP</dt>
                            <dd class="text-md text-gray-900 col-span-2"
                                x-text="formatDateTime(currentInfoUpload?.sftp_uploaded_at)">
                            </dd>
                        </div>

                        <!-- Record Processati -->
                        <div class="py-3 grid grid-cols-3">
                            <dt class="text-md font-medium text-gray-500">Record Processati</dt>
                            <dd class="text-md text-gray-900 col-span-2">
                                <template x-if="currentInfoUpload?.processing_stats?.processed_records !== undefined">
                                    <span
                                        x-text="currentInfoUpload.processing_stats.processed_records + ' / ' + (currentInfoUpload.total_records || currentInfoUpload.processing_stats.total_records || 0)"></span>
                                </template>
                                <template x-if="currentInfoUpload?.processing_stats?.processed_records === undefined">
                                    <span
                                        x-text="(currentInfoUpload?.processed_records || 0) + ' / ' + (currentInfoUpload?.total_records || 0)"></span>
                                </template>
                            </dd>
                        </div>

                        <!-- Sezione Errori CSV -->
                        <template x-if="currentInfoUpload?.status === 'error'">
                            <div class="py-4">
                                <dt class="text-md font-medium text-gray-500 mb-2">Dettagli Errori CSV</dt>
                                <dd class="mt-2">
                                    <div class="bg-red-50 p-4 rounded-xl">
                                        <div class="text-md text-red-700">
                                            <template x-if="currentInfoUpload?.processing_stats?.error_details">
                                                <div>
                                                    <p class="font-medium mb-2">Righe con errori:</p>
                                                    <ul class="list-disc pl-5 space-y-1">
                                                        <template
                                                            x-for="error in currentInfoUpload.processing_stats.error_details"
                                                            :key="error.line">
                                                            <li>
                                                                <span x-text="`Riga ${error.line}: ${error.error}`"></span>
                                                            </li>
                                                        </template>
                                                    </ul>
                                                </div>
                                            </template>
                                            <template x-if="!currentInfoUpload?.processing_stats?.error_details">
                                                <p>Nessun dettaglio errore disponibile</p>
                                            </template>
                                        </div>
                                    </div>
                                </dd>
                            </div>
                        </template>

                        <!-- Errori SFTP -->
                        <template x-if="currentInfoUpload?.sftp_status === 'error'">
                            <div class="py-4">
                                <dt class="text-md font-medium text-gray-500 mb-2">Errore SFTP</dt>
                                <dd class="mt-2">
                                    <div class="bg-red-50 p-4 rounded-xl">
                                        <div class="text-md text-red-700">
                                            <p
                                                x-text="currentInfoUpload.sftp_error_message || 'Nessun dettaglio errore disponibile'">
                                            </p>
                                        </div>
                                    </div>
                                </dd>
                            </div>
                        </template>
                    </dl>

                    <!-- Bottoni -->
                    <div class="mt-6 space-y-3">
                        <button x-show="currentInfoUpload?.status === 'published'" @click="unpublishUpload"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-yellow-600 hover:bg-yellow-700">
                            <i data-lucide="archive" class="h-5 w-5 mr-2"></i>
                            Rimuovi da Pubblicazione
                        </button>

                        <button x-show="currentInfoUpload?.status !== 'published'" @click="deleteUpload"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-md font-medium text-white bg-red-600 hover:bg-red-700">
                            <i data-lucide="trash-2" class="h-5 w-5 mr-2"></i>
                            Elimina File
                        </button>
                    </div>

                    <button type="button" @click="closeInfoModal"
                        class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-custom-activeItem sm:text-md">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>

        <!-- Modale Export -->
        <div x-show="showExportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showExportModal = false"
                    aria-hidden="true"></div>

                <div
                    class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="download" class="h-6 w-6 text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Export File</h3>
                            <div class="mt-2">
                                <p class="text-md text-gray-500">Scegli come procedere con l'export del file:</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-col space-y-3">
                        <!-- Download locale -->
                        <button type="button" @click="downloadExport(selectedUpload.id)"
                            class="inline-flex w-full justify-center items-center rounded-xl bg-blue-100 px-3 py-2 text-md font-semibold text-blue-600 shadow-sm hover:bg-blue-200">
                            <i data-lucide="download" class="h-5 w-5 mr-2"></i>
                            Download File
                        </button>

                        <!-- Upload SFTP -->
                        <button type="button" @click="uploadToSftp(selectedUpload.id)"
                            class="inline-flex w-full justify-center items-center rounded-xl bg-custom-activeItem px-3 py-2 text-md font-semibold text-white shadow-sm hover:bg-custom-activeItem/90">
                            <i data-lucide="upload" class="h-5 w-5 mr-2"></i>
                            Carica su FTP
                        </button>

                        <button type="button" @click="showExportModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-md font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Annulla
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/upload-manager.js') }}"></script>
@endpush
