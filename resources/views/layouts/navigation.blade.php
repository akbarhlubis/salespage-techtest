<nav x-data="{ open: false }" class="gradient-bg text-white sticky top-0 z-50 shadow-lg">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between h-14">
            {{-- Logo + primary links --}}
            <div class="flex items-center gap-6">
                <a href="{{ route('sales-pages.index') }}" class="font-bold text-lg flex items-center gap-2">
                    <span aria-hidden="true">⚡</span><span>SalesPage AI</span>
                </a>

                <div class="hidden sm:flex items-center gap-1">
                    <a href="{{ route('sales-pages.index') }}"
                        class="text-sm px-3 py-1.5 rounded-lg transition {{ request()->routeIs('sales-pages.index') ? 'bg-white/15 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                        My Pages
                    </a>
                    <a href="{{ route('sales-pages.create') }}"
                        class="text-sm px-3 py-1.5 rounded-lg transition {{ request()->routeIs('sales-pages.create') ? 'bg-white/15 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                        + New
                    </a>
                </div>
            </div>

            {{-- User dropdown (desktop) --}}
            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm text-white/80 hover:text-white hover:bg-white/10 transition">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger (mobile) --}}
            <div class="sm:hidden">
                <button @click="open = !open" class="p-2 rounded-md text-white/70 hover:text-white hover:bg-white/10 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden border-t border-white/10">
        <div class="px-4 py-3 space-y-1">
            <a href="{{ route('sales-pages.index') }}"
                class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('sales-pages.index') ? 'bg-white/15' : 'hover:bg-white/10' }}">
                My Pages
            </a>
            <a href="{{ route('sales-pages.create') }}"
                class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('sales-pages.create') ? 'bg-white/15' : 'hover:bg-white/10' }}">
                + New
            </a>
        </div>
        <div class="border-t border-white/10 px-4 py-3">
            <div class="text-white/90 text-sm font-medium">{{ Auth::user()->name }}</div>
            <div class="text-white/50 text-xs mb-2">{{ Auth::user()->email }}</div>
            <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-lg text-sm hover:bg-white/10">Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-white/10">Log Out</button>
            </form>
        </div>
    </div>
</nav>
