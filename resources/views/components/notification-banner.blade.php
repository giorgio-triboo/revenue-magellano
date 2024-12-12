<div
    x-show="notifications.show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform -translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform -translate-y-2"
    class="mb-6 rounded-xl p-4 border"
    :class="{
        'bg-green-50 border-green-200': notifications.type === 'success',
        'bg-red-50 border-red-200': notifications.type === 'error',
        'bg-yellow-50 border-yellow-200': notifications.type === 'warning'
    }"
>
    <div class="flex">
        <div class="flex-shrink-0">
            <template x-if="notifications.type === 'success'">
                <i data-lucide="check-circle" class="h-5 w-5 text-green-400"></i>
            </template>
            <template x-if="notifications.type === 'error'">
                <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
            </template>
            <template x-if="notifications.type === 'warning'">
                <i data-lucide="alert-triangle" class="h-5 w-5 text-yellow-400"></i>
            </template>
        </div>
        <div class="ml-3">
            <h3 class="text-md font-medium"
                :class="{
                    'text-green-800': notifications.type === 'success',
                    'text-red-800': notifications.type === 'error',
                    'text-yellow-800': notifications.type === 'warning'
                }">
                <span x-text="notifications.title || (notifications.type === 'success' ? 'Operazione completata' : notifications.type === 'error' ? 'Errore' : 'Attenzione')"></span>
            </h3>
            <div class="mt-2 text-md"
                :class="{
                    'text-green-700': notifications.type === 'success',
                    'text-red-700': notifications.type === 'error',
                    'text-yellow-700': notifications.type === 'warning'
                }"
                x-text="notifications.message">
            </div>
        </div>
        <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
                <button @click="notifications.show = false" type="button"
                    class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="{
                        'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-green-600 focus:ring-offset-green-50': notifications.type === 'success',
                        'bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600 focus:ring-offset-red-50': notifications.type === 'error',
                        'bg-yellow-50 text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600 focus:ring-offset-yellow-50': notifications.type === 'warning'
                    }">
                    <span class="sr-only">Chiudi</span>
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>
    </div>
</div>