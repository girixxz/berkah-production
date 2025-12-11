<header class="sticky top-0 z-30 bg-white border-b border-gray-200 w-full max-w-full">
    <div class="flex items-center justify-between px-4 py-3 w-full max-w-full">
        <!-- Left: Hamburger -->
        <div class="flex items-center space-x-4 flex-shrink-0">
            <button @click="$dispatch('sidebar-toggle')"
                class="text-gray-600 hover:text-gray-800 focus:outline-none p-2 rounded-md hover:bg-gray-100 md:inline-flex"
                aria-label="Toggle sidebar" title="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Center: Nav (Highlight & Calendar) -->
        @php
            $navClasses = function (string $pattern) {
                $base = 'px-6 py-2 rounded-md transition-all duration-200';
                return request()->routeIs($pattern)
                    ? $base . ' bg-primary-light font-medium hover:bg-gray-100'
                    : $base . ' text-gray-700 hover:bg-gray-100 hover:text-gray-900';
            };
        @endphp

        <nav class="flex-1 flex items-center justify-center min-w-0">
            <ul class="flex items-center text-sm md:text-[14px] gap-3">
                <li>
                    <a href="{{ route('highlights') }}" target="_blank" class="{{ $navClasses('highlights') }}">
                        Highlights
                    </a>
                </li>
                <li>
                    <a href="{{ route('calendar') }}" target="_blank" class="{{ $navClasses('calendar') }}">
                        Calendar
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Right: Notification Bell (Owner Only) + User -->
        <div class="flex items-center gap-3 flex-shrink-0">
        @php
            $profile_name = auth()->user()?->fullname;
            $user_role = auth()->user()?->role;

            // Ganti 'photo_url' dengan nama kolom avatar di tabel users milikmu
            $raw = auth()->user()?->img_url;

            // Jika path lokal, konversi ke URL publik; jika sudah http(s), pakai apa adanya
            $avatarUrl = !empty($raw)
                ? (\Illuminate\Support\Str::startsWith($raw, ['http://', 'https://'])
                    ? $raw
                    : \Illuminate\Support\Facades\Storage::url($raw))
                : 'https://i.pravatar.cc/40?u=' . urlencode(auth()->user()->id ?? auth()->user()->username);
        @endphp
        <div class="flex items-center sm:space-x-4">
            
            {{-- Notification Bell (Owner Only) --}}
            @if($user_role === 'owner')
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @keydown.escape.window="open = false"
                        class="relative p-2 rounded-full hover:bg-gray-100 focus:outline-none"
                        aria-label="Notifications">
                        {{-- Bell Icon --}}
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        {{-- Badge Count --}}
                        <span id="notification-badge-count" 
                              style="display: none; position: absolute; top: 0; right: 0; background-color: #ef4444; color: white; font-size: 10px; font-weight: bold; border-radius: 9999px; min-width: 18px; height: 18px; padding: 0 4px; align-items: center; justify-content: center;">
                            0
                        </span>
                    </button>

                    {{-- Dropdown Notification --}}
                    <div x-cloak x-show="open" @click.outside="open = false" x-transition.opacity
                        class="fixed mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
                        style="right: 1rem; width: min(384px, calc(100vw - 2rem));">
                        {{-- Header --}}
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                        </div>
                        {{-- Notification List --}}
                        <div id="notification-list" class="max-h-80 overflow-y-auto">
                            {{-- Empty state --}}
                            <div id="notification-empty" style="display: none;" class="px-4 py-8 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-sm">No pending payments</p>
                            </div>
                            {{-- Items will be inserted here by JS --}}
                        </div>
                    </div>
                </div>
            @endif
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @keydown.escape.window="open = false"
                    class="flex items-center rounded-md hover:bg-gray-100 px-3 py-2 focus:outline-none cursor-pointer"
                    x-bind:aria-expanded="open" aria-haspopup="menu" aria-controls="userDropdown">
                    <img class="w-8 h-8 rounded-full" src="{{ $avatarUrl }}" alt="User avatar" />
                    <span class="ml-2 text-gray-700 font-medium hidden sm:block">{{ $profile_name }}</span>
                    <svg class="hidden sm:block w-4 h-4 ml-1 text-gray-500 transition-transform duration-200"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round"
                        stroke-linejoin="round" x-bind:class="open ? 'rotate-180' : ''">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <!-- Dropdown -->
                <div id="userDropdown" x-cloak x-show="open" @click.outside="open = false" x-transition.opacity
                    class="fixed mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg py-1 z-50"
                    style="right: 1rem;"
                    role="menu">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="flex items-center w-full text-left px-4 py-2 text-red-600 hover:bg-gray-100 cursor-pointer"
                            role="menuitem">
                            <x-icons.logout class="" />
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
