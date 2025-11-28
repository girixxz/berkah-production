<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-cache-control" content="no-cache">
    <meta name="turbo-prefetch" content="false">
    <title>@yield('title')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Alpine.js sudah di-bundle dalam app.js (self-hosted) --}}
    {{-- penting buat sembunyiin elemen x-cloak saat load --}}
    <style>
        [x-cloak] {
            display: none !important
        }

        /* Prevent white flash on page load */
        html,
        body {
            background-color: #f9fafb;
            /* Same as bg-gray-light/bg-gray-50 */
        }

        /* NProgress custom styling */
        #nprogress .bar {
            background: #3b82f6 !important; /* Blue-500, sesuaikan dengan primary color Anda */
            height: 3px !important;
            z-index: 9999 !important;
        }

        #nprogress .peg {
            box-shadow: 0 0 10px #3b82f6, 0 0 5px #3b82f6 !important;
        }
    </style>
</head>

{{-- tambahkan x-data + listener event toggle --}}

<body x-data="{
    sidebarOpen: false,
    userPreference: null, // null = auto, true = force open, false = force close

    init() {
        // Load user preference dari localStorage
        const saved = localStorage.getItem('sidebarUserPreference');
        if (saved !== null) {
            this.userPreference = saved === 'true';
        }

        // Set initial state
        const isLgOrLarger = window.matchMedia('(min-width: 1024px)').matches;
        if (this.userPreference !== null) {
            // Respect user preference (hanya di lg+)
            this.sidebarOpen = isLgOrLarger && this.userPreference;
        } else {
            // Auto behavior: buka di lg+, tutup di mobile
            this.sidebarOpen = isLgOrLarger;
        }

        // Listen untuk perubahan ukuran layar
        window.addEventListener('resize', () => {
            const isLgOrLarger = window.matchMedia('(min-width: 1024px)').matches;

            if (!isLgOrLarger) {
                // Di mobile/tablet: selalu tutup saat resize
                this.sidebarOpen = false;
            } else {
                // Di lg+: respect user preference
                if (this.userPreference !== null) {
                    this.sidebarOpen = this.userPreference;
                } else {
                    this.sidebarOpen = true; // Default buka di lg+
                }
            }
        });
    },

    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;

        // Save preference hanya untuk lg+ screen
        const isLgOrLarger = window.matchMedia('(min-width: 1024px)').matches;
        if (isLgOrLarger) {
            this.userPreference = this.sidebarOpen;
            localStorage.setItem('sidebarUserPreference', this.sidebarOpen);
        }
    }
}" @sidebar-toggle.window="toggleSidebar()" class="h-screen flex bg-gray-light">

    {{-- SIDEBAR WRAPPER + OVERLAY --}}
    {{-- Di mobile/tablet: sidebar overlay (fixed), di LG+: sidebar push konten (flex) --}}
    <div x-cloak class="hidden lg:block relative z-40 flex-shrink-0 overflow-hidden"
        :class="sidebarOpen ? 'w-64' : 'w-0'" :style="sidebarOpen ? '' : 'transition: width 0ms'">
        {{-- container sidebar untuk LG+ --}}
        <div class="fixed inset-y-0 left-0 w-64 transform" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            :style="sidebarOpen ? 'transition: transform 300ms ease-out' : 'transition: none'">
            @include('partials.sidebar')
        </div>
    </div>

    {{-- Sidebar Mobile/Tablet: Overlay di atas konten --}}
    <div class="lg:hidden">
        {{-- overlay gelap saat sidebar terbuka (hanya di mobile/tablet) --}}
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-black/40 z-40"
            @click="$dispatch('sidebar-toggle')" aria-hidden="true"></div>

        {{-- container sidebar mobile/tablet: overlay --}}
        <div x-cloak class="fixed inset-y-0 left-0 w-64 transform z-50"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            :style="sidebarOpen ? 'transition: transform 300ms ease-out' : 'transition: none'">
            @include('partials.sidebar')
        </div>
    </div>

    {{-- MAIN - konten akan otomatis adjust sesuai lebar sidebar di LG+ --}}
    <div x-cloak class="flex-1 flex flex-col overflow-hidden" data-turbo-permanent>
        @include('partials.navbar')

        <main id="main-content" class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>

    {{-- Notif Toast --}}
    <x-toast-notif />

    {{-- Script --}}
    @stack('scripts')
</body>

</html>
