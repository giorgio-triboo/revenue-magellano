@extends('layouts.auth')

@section('title', 'Login')

@section('content')
   <div x-data="loginForm" class="min-h-[calc(100vh-200px)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
       <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
           <div class="bg-custom-card py-8 px-4 shadow-md sm:rounded-xl sm:px-10">
               <!-- Header -->
               <div class="sm:mx-auto sm:w-full sm:max-w-md">
                   <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                       Accedi
                   </h2>
                   <p class="mt-2 text-center text-sm text-gray-600">
                       Inserisci le tue credenziali per accedere
                   </p>
               </div>

               <!-- Alert Messages -->
               <div class="space-y-4 mt-4">
                   @if (session('error'))
                       <div class="rounded-xl bg-red-50 p-4 border border-red-200">
                           <div class="flex">
                               <div class="flex-shrink-0">
                                   <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                               </div>
                               <div class="ml-3">
                                   <p class="text-sm text-red-700">{{ session('error') }}</p>
                               </div>
                           </div>
                       </div>
                   @endif

                   @if (session('warning'))
                       <div class="rounded-xl bg-yellow-50 p-4 border border-yellow-200">
                           <div class="flex">
                               <div class="flex-shrink-0">
                                   <i data-lucide="alert-triangle" class="h-5 w-5 text-yellow-400"></i>
                               </div>
                               <div class="ml-3">
                                   <p class="text-sm text-yellow-700">{{ session('warning') }}</p>
                               </div>
                           </div>
                       </div>
                   @endif

                   @if (session('success'))
                       <div class="rounded-xl bg-green-50 p-4 border border-green-200">
                           <div class="flex">
                               <div class="flex-shrink-0">
                                   <i data-lucide="check-circle" class="h-5 w-5 text-green-400"></i>
                               </div>
                               <div class="ml-3">
                                   <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                               </div>
                           </div>
                       </div>
                   @endif
               </div>

               <!-- Login Form -->
               <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
                   @csrf
                   <div class="space-y-4">
                       <!-- Email Input -->
                       <div>
                           <label for="email" class="block text-sm font-medium text-gray-700">
                               Email <span class="text-red-500">*</span>
                           </label>
                           <div class="mt-1 relative rounded-xl shadow-sm">
                               <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                   <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                               </div>
                               <input id="email" 
                                      name="email" 
                                      type="email" 
                                      required
                                      class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                      value="{{ old('email') }}"
                                      placeholder="La tua email">
                           </div>
                           @error('email')
                               <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                           @enderror
                       </div>

                       <!-- Password Input -->
                       <div>
                           <label for="password" class="block text-sm font-medium text-gray-700">
                               Password <span class="text-red-500">*</span>
                           </label>
                           <div class="mt-1 relative rounded-xl shadow-sm">
                               <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                   <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                               </div>
                               <input id="password" 
                                      :type="showPassword ? 'text' : 'password'"
                                      name="password" 
                                      required
                                      class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-custom-activeItem focus:border-custom-activeItem sm:text-sm"
                                      placeholder="La tua password">
                               <button type="button" 
                                       @click="showPassword = !showPassword"
                                       class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                   <i data-lucide="eye" x-show="!showPassword" class="h-5 w-5 text-gray-400"></i>
                                   <i data-lucide="eye-off" x-show="showPassword" class="h-5 w-5 text-gray-400"></i>
                               </button>
                           </div>
                           @error('password')
                               <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                           @enderror
                       </div>
                   </div>

                   <!-- Remember Me & Forgot Password -->
                   <div class="flex items-center justify-between">
                       <div class="flex items-center">
                           <input id="remember" 
                                  name="remember" 
                                  type="checkbox"
                                  class="h-4 w-4 text-custom-activeItem focus:ring-custom-activeItem border-gray-200 rounded">
                           <label for="remember" class="ml-2 block text-sm text-gray-900">
                               Ricordami
                           </label>
                       </div>

                       <div class="text-sm">
                           <a href="{{ route('password.request') }}"
                               class="font-medium text-custom-activeItem hover:text-custom-activeItem/90">
                               Password dimenticata?
                           </a>
                       </div>
                   </div>

                   <!-- Submit Button -->
                   <div>
                       <button type="submit"
                           class="w-full flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-custom-activeItem hover:bg-custom-activeItem/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-custom-activeItem">
                           Accedi
                       </button>
                   </div>
               </form>

               <!-- Register Link -->
               <div class="mt-6 text-center">
                   <p class="text-sm text-gray-600">
                       Non hai un account?
                       <a href="{{ route('register') }}"
                           class="font-medium text-custom-activeItem hover:text-custom-activeItem/90">
                           Registrati
                       </a>
                   </p>
               </div>
           </div>
       </div>
   </div>
@endsection

@push('scripts')
<script>
   document.addEventListener('alpine:init', () => {
       Alpine.data('loginForm', () => ({
           showPassword: false,
       }))
   })
</script>
@endpush