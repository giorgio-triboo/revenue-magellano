<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Termini e Condizioni - Galileo</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Scripts -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-custom-platform">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-xl">
            <div class="bg-custom-card py-8 px-4 shadow-md sm:rounded-xl sm:px-10">
                <!-- Header -->
                <div class="sm:mx-auto sm:w-full sm:max-w-md">
                    <h2 class="mt-2 text-center text-3xl font-bold text-gray-900">
                        Termini e Condizioni
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Per continuare è necessario leggere i termini e le condizioni fino in fondo
                    </p>
                </div>

                <div class="mt-8">
                    <!-- Area scrollabile dei termini e condizioni -->
                    <div class="h-96 overflow-y-auto p-4 border border-gray-200 rounded-xl bg-white">
                        <!-- Placeholder del contenuto dei termini -->
                        <h3 class="text-lg font-medium text-gray-900 mb-4">1. Introduzione</h3>
                        <p class="mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
                            incididunt ut labore et dolore magna aliqua...</p>

                        <h3 class="text-lg font-medium text-gray-900 mb-4">2. Definizioni</h3>
                        <p class="mb-4">Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
                            aliquip ex ea commodo consequat...</p>

                        <h3 class="text-lg font-medium text-gray-900 mb-4">3. Servizi Offerti</h3>
                        <p class="mb-4">Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore
                            eu fugiat nulla pariatur...</p>

                        <h3 class="text-lg font-medium text-gray-900 mb-4">4. Responsabilità</h3>
                        <p class="mb-4">Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia
                            deserunt mollit anim id est laborum...</p>

                        <h3 class="text-lg font-medium text-gray-900 mb-4">5. Privacy e Dati Personali</h3>
                        <p class="mb-4">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                            doloremque laudantium...</p>

                        <h3 class="text-lg font-medium text-gray-900 mb-4">6. Modifiche ai Termini</h3>
                        <p class="mb-4">Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit...
                        </p>

                        <h3 class="text-lg font-medium text-gray-900 mb-4">7. Legge Applicabile</h3>
                        <p class="mb-4">At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis
                            praesentium voluptatum...</p>
                    </div>

                    <form action="{{ route('terms.accept') }}" method="POST" class="mt-8 space-y-6">
                        @csrf

                        <!-- Checkbox di accettazione -->
                        <div class="flex items-center">
                            <input type="checkbox" name="accept_terms" id="accept_terms" value="1"
                                class="h-4 w-4 text-custom-activeItem focus:ring-custom-activeItem border-gray-200 rounded"
                                disabled>
                            <label for="accept_terms" class="ml-2 block text-sm text-gray-900">
                                Dichiaro di aver letto e di accettare i termini e le condizioni
                            </label>
                        </div>

                        <!-- Pulsante di submit -->
                        <div>
                            <button type="submit" disabled
                                class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white transition-all duration-200 bg-gray-300 cursor-not-allowed">
                                Scorri fino in fondo per accettare
                            </button>
                        </div>
                    </form>

                    <!-- Pulsante Torna al Login -->
                    <div class="mt-4 space-y-4">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-custom-card text-gray-500">oppure</span>
                            </div>
                        </div>

                        <button type="button" onclick="resetLoginAndRedirect()"
                            class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 rounded-xl shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem transition-all duration-200">
                            <i data-lucide="log-out" class="h-5 w-5 mr-2"></i>
                            Torna al Login
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const scrollContainer = document.querySelector('.h-96.overflow-y-auto');
            const acceptButton = document.querySelector('button[type="submit"]');
            const acceptCheckbox = document.querySelector('#accept_terms');

            // Inizializza lo stato dei controlli
            function updateControls(hasRead) {
                acceptButton.disabled = !hasRead;
                acceptCheckbox.disabled = !hasRead;
                acceptButton.classList.toggle('bg-gray-300', !hasRead);
                acceptButton.classList.toggle('cursor-not-allowed', !hasRead);
                acceptButton.classList.toggle('bg-custom-activeItem', hasRead);
            }

            // Funzione per controllare lo stato dello scroll
            function checkScroll() {
                const isScrolledToBottom =
                    scrollContainer.scrollHeight - scrollContainer.scrollTop <=
                    scrollContainer.clientHeight + 1; // Tolleranza
                console.log('ScrollHeight:', scrollContainer.scrollHeight);
                console.log('ScrollTop:', scrollContainer.scrollTop);
                console.log('ClientHeight:', scrollContainer.clientHeight);
                console.log('Scrolled to bottom:', isScrolledToBottom);

                if (isScrolledToBottom) {
                    updateControls(true); // Abilita i controlli
                }
            }

            // Assegna l'evento di scroll al contenitore
            if (scrollContainer) {
                scrollContainer.addEventListener('scroll', checkScroll);
            }

            // Inizializza lo stato dei controlli (disabilitati)
            updateControls(false);
        });

        // Logout e redirect migliorato
        function resetLoginAndRedirect() {
            fetch('{{ route('logout') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (response.ok) {
                        console.log('Logout effettuato con successo');
                        // Cancella i cookie
                        document.cookie.split(";").forEach(function(c) {
                            document.cookie = c.replace(/^ +/, "")
                                .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                        });
                        localStorage.clear();
                        sessionStorage.clear();
                        window.location.href = '{{ route('login') }}';
                    } else {
                        console.error('Errore durante il logout:', response.status);
                    }
                })
                .catch(error => {
                    console.error('Errore durante il logout:', error);
                    alert('Si è verificato un errore durante il logout. Riprova.');
                });
        }

        // Inizializza le icone Lucide
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>

</html>
