<nav class="flex flex-col h-full">
    <div class="flex-1 space-y-1 px-3 py-4">
        <!-- Dashboard - Visibile a tutti -->
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3 py-2.5 text-md font-medium rounded-xl group {{ request()->routeIs('dashboard') ? 'bg-custom-activeItem text-custom-textSec' : 'text-custom-textMain hover:bg-white/5' }}">
            <i data-lucide="layout-grid" class="w-5 h-5"></i>
            <span>Dashboard</span>
        </a>
        <!-- Consuntivi -->
        <a href="{{ route('statements.index') }}"
            class="flex items-center gap-3 px-3 py-2.5 text-md font-medium rounded-xl group {{ request()->routeIs('statements.*') ? 'bg-custom-activeItem text-custom-textSec' : 'text-custom-textMain hover:bg-white/5' }}">
            <i data-lucide="file-text" class="w-5 h-5"></i>
            <span>Consuntivi</span>
        </a>
        <!-- Menu Admin -->
        @if (auth()->user()->isAdmin())
            <!-- Upload -->
            <a href="{{ route('uploads.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 text-md font-medium rounded-xl group {{ request()->routeIs('uploads.*') ? 'bg-custom-activeItem text-custom-textSec' : 'text-custom-textMain hover:bg-white/5' }}">
                <i data-lucide="upload" class="w-5 h-5"></i>
                <span>Upload</span>
            </a>
            <!-- Lista Editori -->
            <a href="{{ route('publishers.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 text-md font-medium rounded-xl group {{ request()->routeIs('publishers.*') ? 'bg-custom-activeItem text-custom-textSec' : 'text-custom-textMain hover:bg-white/5' }}">
                <i data-lucide="book-open" class="w-5 h-5"></i>
                <span>Lista Editori</span>
            </a>
            <a href="{{ route('users.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 text-md font-medium rounded-xl group {{ request()->routeIs('users.*') ? 'bg-custom-activeItem text-custom-textSec' : 'text-custom-textMain hover:bg-white/5' }}">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span>Gestione Profili</span>
            </a>
        @endif

        @if (auth()->user()->isPublisher())
            <!-- Assistenza -->
            <a href="{{ route('support.show') }}"
                class="flex items-center gap-3 px-3 py-2.5 text-md font-medium rounded-xl group {{ request()->routeIs('support.*') ? 'bg-custom-activeItem text-custom-textSec' : 'text-custom-textMain hover:bg-white/5' }}">
                <i data-lucide="help-circle" class="w-5 h-5"></i>
                <span>Assistenza</span>
            </a>
        @endif
        @if (auth()->user()->isAdmin())
            <!-- Lista Editori -->
            <a href="#"
                class="flex items-center gap-3 px-3 py-2.5 text-md font-medium rounded-xl group {{ request()->routeIs('publishers.*') ? 'bg-custom-activeItem text-custom-textSec' : 'text-custom-textMain hover:bg-white/5' }}">
                <i data-lucide="settings" class="w-5 h-5"></i>
                <span>Impostazioni</span>
            </a>
        @endif

    </div>

    <!-- Profile and Logout Section -->
    <div class="mt-auto border-t border-custom-textMain/30">
        <div class="px-6 py-4 space-y-4">

            <a href="{{ route('profile.show') }}"
                class="flex items-center gap-3 px-3 py-2.5 text-md font-medium rounded-xl group {{ request()->routeIs('profile.*') ? 'bg-custom-activeItem text-custom-textSec' : 'text-custom-textMain hover:bg-white/5' }}">
                <i data-lucide="user" class="w-5 h-5"></i>
                <span>Profilo Utente</span>
            </a>

            <!-- Profile Info -->
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-black/20">
                    <span class="text-md font-medium text-custom-textMain">
                        {{ substr(auth()->user()->first_name, 0, 1) }}{{ substr(auth()->user()->last_name, 0, 1) }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-md font-medium text-custom-textMain truncate">
                        {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                    </p>
                    <p class="text-xs text-custom-textMain truncate">
                        {{ auth()->user()->email }}
                    </p>
                </div>
            </div>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex w-full items-center gap-3 px-3 py-2.5 text-md font-medium text-red-500 hover:bg-white/5 rounded-xl group">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</nav>
