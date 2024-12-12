@extends('layouts.auth')

@section('title', 'Recupero Password')

@extends('layouts.auth')

@section('title', 'Recupero Password')

@section('content')
    <div x-data="forgotPasswordForm()"
        class="min-h-[calc(100vh-200px)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="bg-custom-card py-8 px-4 shadow-md sm:rounded-xl sm:px-10">
                <!-- Header -->
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-gray-900">
                        Recupero Password
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Inserisci il tuo indirizzo email per ricevere il link di recupero
                    </p>
                </div>

                <!-- Success Message -->
                @if (session('success'))
                    <div class="mt-4 p-4 rounded-xl bg-green-50 border border-gray-200">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="check-circle" class="h-5 w-5 text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    {{ session('success') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Info Message -->
                @if (session('info'))
                    <div class="mt-4 p-4 rounded-xl bg-yellow-50 border border-gray-200">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="alert-circle" class="h-5 w-5 text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">
                                    {{ session('info') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Error Message -->
                @error('email')
                    <div class="mt-4 p-4 rounded-xl bg-red-50 border border-gray-200">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    {{ $message }}
                                </p>
                            </div>
                        </div>
                    </div>
                @enderror

                <!-- Form -->
                <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-6">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input id="email" name="email" type="email" required
                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                :class="{ 'border-red-300': errors.email }" placeholder="Il tuo indirizzo email"
                                value="{{ old('email') }}">
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem disabled:opacity-50 disabled:cursor-not-allowed">
                            <span>Invia link di recupero</span>
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="{{ route('login') }}"
                            class="font-medium text-custom-activeItem hover:text-custom-activeItem/90">
                            Torna al login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function forgotPasswordForm() {
            return {
                errors: {
                    email: false
                }
            }
        }
    </script>
@endpush
