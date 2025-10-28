<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Berkah Bermanfaat - Login</title>

    {{-- Pre-load critical assets untuk persiapan setelah login --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Pre-load Google Fonts untuk dashboard nanti --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen bg-gray-light flex items-center justify-center">
    <main class="w-full max-w-md px-4 py-10">
        <!-- Logo -->
        <div class="flex justify-center mb-8">
            <img src="{{ asset('images/brand.svg') }}" alt="Berkah Bermanfaat" class="h-24 md:h-28 w-auto">
        </div>

        @if ($errors->has('username') || $errors->has('password') || session('auth_error'))
            <div class="text-center mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @if ($errors->has('username') && $errors->has('password'))
                    {{-- Keduanya kosong --}}
                    <p>Username and password are required.</p>
                @elseif ($errors->has('username') || $errors->has('password'))
                    {{-- Salah satu kosong --}}
                    <p>This field is required.</p>
                @elseif (session('auth_error'))
                    {{-- Kredensial salah --}}
                    <p>{{ session('auth_error') }}</p>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-4" novalidate>
            @csrf

            <!-- Username -->
            <div class="space-y-1">
                <label for="username" class="block text-sm text-font-base">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <!-- icon user -->
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <!-- user-circle, stroke halus -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-font-muted" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M7 18a5 5 0 0 1 10 0"></path>
                                <path d="M15 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path>
                            </svg>
                        </span>

                    </span>
                    <input id="username" name="username" type="text" autocomplete="username" required
                        class="block w-full h-10 rounded-md border border-gray-solid bg-white text-sm
                    pl-10 pr-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Username" />
                </div>
            </div>

            <!-- Password -->
            <div class="space-y-1">
                <label for="password" class="block text-sm text-font-base">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <!-- icon lock -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-font-muted" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V8.25a4.5 4.5 0 10-9 0V10.5" />
                            <rect x="4.5" y="10.5" width="15" height="9" rx="2" ry="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>

                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        minlength="6"
                        class="block w-full h-10 rounded-md border border-slate-300 bg-white text-sm
                        pl-10 pr-10 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Password" />

                    <!-- Toggle password -->
                    <button type="button" id="togglePassword"
                        class="absolute inset-y-0 right-0 px-3 flex items-center focus:outline-none"
                        aria-label="Tampilkan/sembunyikan password">
                        <!-- eye -->
                        <svg data-eye class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5s8.577 3.01 9.964 7.178c.07.21.07.434 0 .644C20.577 16.49 16.64 19.5 12 19.5S3.423 16.49 2.036 12.322z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <!-- eye-off -->
                        <svg data-eye-off class="h-5 w-5 text-gray-400 hidden" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3l18 18M9.88 9.88A3 3 0 0012 15a3 3 0 002.12-.88M6.11 6.11C3.95 7.63 2.39 9.64 2.036 11.678a1.012 1.012 0 000 .644C3.423 16.49 7.36 19.5 12 19.5c1.623 0 3.16-.33 4.536-.93M14.12 14.12L12 12m6.314-2.314c.776.706 1.42 1.51 1.65 2.492.07.21.07.434 0 .644-.38 1.33-1.107 2.52-2.056 3.53" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- Remember me -->
            <div class="flex items-center">
                <label class="inline-flex items-center gap-2 text-sm text-font-base select-none">
                    <input id="remember" name="remember" type="checkbox"
                        class="rounded border-slate-300 text-primary focus:ring-primary">
                    Remember Me
                </label>
            </div>

            <!-- Error box (client-side) -->
            <div id="errorBox" class="hidden text-sm text-red-600"></div>

            <!-- Submit -->
            <button type="submit"
                class="w-full h-10 rounded-md bg-primary text-white font-semibold
                    hover:bg-primary-dark focus:outline-none focus:ring-4 focus:ring-primary/30
                    transition">
                Login
            </button>
        </form>
    </main>
    <!-- JS khusus untuk toggle password (diletakkan dekat komponennya) -->
    <script>
        (function() {
            const pwd = document.getElementById('password');
            const btn = document.getElementById('togglePassword');
            const eye = btn.querySelector('[data-eye]');
            const eyeOff = btn.querySelector('[data-eye-off]');
            let visible = false;

            btn.addEventListener('click', function() {
                visible = !visible;
                pwd.type = visible ? 'text' : 'password';
                eye.classList.toggle('hidden', visible);
                eyeOff.classList.toggle('hidden', !visible);
            });
        })();
    </script>
</body>

</html>
