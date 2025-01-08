@extends('layouts.auth')

@section('title', 'Reset Password')

@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <div x-data="resetPasswordForm()"
        class="min-h-[calc(100vh-200px)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="bg-custom-card py-8 px-4 shadow-md sm:rounded-xl sm:px-10">
                <!-- Header -->
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-gray-900">
                        Reset Password
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Inserisci la tua nuova password
                    </p>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="mt-4 p-4 rounded-xl bg-red-50 border border-gray-200">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="list-disc list-inside text-sm text-red-700">
                                    @foreach ($errors->all() as $error)
                                        {{ $error }}
                                    @endforeach
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-6">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="user" value="{{ $user_id }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="space-y-4">
                        

                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Nuova Password <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                                </div>
                                <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                                    x-model="password" @input="checkPasswordStrength"
                                    class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                    placeholder="Inserisci la nuova password">
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i data-lucide="eye" x-show="!showPassword" class="h-5 w-5 text-gray-400"></i>
                                    <i data-lucide="eye-off" x-show="showPassword" class="h-5 w-5 text-gray-400"></i>
                                </button>
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
                                    name="password_confirmation" required x-model="passwordConfirmation"
                                    class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                    :class="{ 'border-red-300': !passwordsMatch && passwordConfirmation }"
                                    placeholder="Conferma la nuova password">
                                <button type="button" @click="showPasswordConfirm = !showPasswordConfirm"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i data-lucide="eye" x-show="!showPasswordConfirm" class="h-5 w-5 text-gray-400"></i>
                                    <i data-lucide="eye-off" x-show="showPasswordConfirm" class="h-5 w-5 text-gray-400"></i>
                                </button>
                            </div>
                            <p x-show="!passwordsMatch && passwordConfirmation" class="mt-2 text-sm text-red-600">
                                Le password non coincidono
                            </p>
                        </div>

                        <!-- Password Requirements -->
                        <div class="mt-4 space-y-2">
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                    :class="{ 'bg-green-500': passwordChecks.minLength, 'bg-gray-200': !passwordChecks
                                        .minLength }">
                                </div>
                                <span class="text-sm"
                                    :class="{ 'text-green-500': passwordChecks.minLength, 'text-gray-500': !passwordChecks
                                            .minLength }">
                                    Minimo 8 caratteri
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                    :class="{ 'bg-green-500': passwordChecks.uppercase, 'bg-gray-200': !passwordChecks
                                        .uppercase }">
                                </div>
                                <span class="text-sm"
                                    :class="{ 'text-green-500': passwordChecks.uppercase, 'text-gray-500': !passwordChecks
                                            .uppercase }">
                                    Almeno una maiuscola
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                    :class="{ 'bg-green-500': passwordChecks.lowercase, 'bg-gray-200': !passwordChecks
                                        .lowercase }">
                                </div>
                                <span class="text-sm"
                                    :class="{ 'text-green-500': passwordChecks.lowercase, 'text-gray-500': !passwordChecks
                                            .lowercase }">
                                    Almeno una minuscola
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                    :class="{ 'bg-green-500': passwordChecks.number, 'bg-gray-200': !passwordChecks.number }">
                                </div>
                                <span class="text-sm"
                                    :class="{ 'text-green-500': passwordChecks.number, 'text-gray-500': !passwordChecks.number }">
                                    Almeno un numero
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-2 rounded-full transition-colors duration-200"
                                    :class="{ 'bg-green-500': passwordChecks.special, 'bg-gray-200': !passwordChecks.special }">
                                </div>
                                <span class="text-sm"
                                    :class="{ 'text-green-500': passwordChecks.special, 'text-gray-500': !passwordChecks
                                        .special }">
                                    Almeno un carattere speciale
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6">
                        <button type="submit" :disabled="!isValid"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-md text-sm font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem disabled:opacity-50 disabled:cursor-not-allowed">
                            Reimposta Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function resetPasswordForm() {
            return {
                password: '',
                passwordConfirmation: '',
                showPassword: false,
                showPasswordConfirm: false,
                passwordChecks: {
                    minLength: false,
                    uppercase: false,
                    lowercase: false,
                    number: false,
                    special: false
                },
                passwordStrength: {
                    score: 0,
                    message: '',
                    percent: 0
                },

                get passwordsMatch() {
                    return this.password === this.passwordConfirmation;
                },

                get isValid() {
                    return this.password &&
                        this.passwordConfirmation &&
                        this.passwordsMatch &&
                        this.passwordStrength.score >= 2 && // Richiede almeno una forza media
                        Object.values(this.passwordChecks).every(check => check);
                },

                checkPasswordStrength() {
                    // Aggiorna i controlli base
                    this.passwordChecks = {
                        minLength: this.password.length >= 8,
                        uppercase: /[A-Z]/.test(this.password),
                        lowercase: /[a-z]/.test(this.password),
                        number: /[0-9]/.test(this.password),
                        special: /[^A-Za-z0-9]/.test(this.password)
                    };

                    // Calcola il punteggio di forza
                    let score = 0;
                    let checksCompleted = Object.values(this.passwordChecks).filter(Boolean).length;

                    // Bonus per lunghezza extra
                    if (this.password.length >= 12) score += 1;
                    if (this.password.length >= 16) score += 1;

                    // Bonus per complessità
                    score += checksCompleted;

                    // Normalizza il punteggio
                    score = Math.min(score, 5);

                    // Aggiorna l'indicatore di forza
                    this.passwordStrength = {
                        score: score,
                        percent: (score / 5) * 100,
                        message: this.getStrengthMessage(score)
                    };
                },

                getStrengthMessage(score) {
                    const messages = {
                        0: 'Molto debole',
                        1: 'Debole',
                        2: 'Media',
                        3: 'Forte',
                        4: 'Molto forte',
                        5: 'Eccellente'
                    };
                    return messages[score] || '';
                },

                init() {
                    this.$watch('password', () => {
                        this.checkPasswordStrength();
                    });

                    this.$watch('passwordConfirmation', () => {
                        // Reset dell'errore quando l'utente modifica la password di conferma
                        if (!this.passwordConfirmation) {
                            this.confirmError = false;
                        }
                    });
                }
            }
        }
    </script>
@endpush
