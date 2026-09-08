<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('code') @yield('standard_title')</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        {{-- Google Fonts: Syne & Plus Jakarta Sans --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">

        {{-- Deteksi Dark Mode Otomatis --}}
        <script>
            (function() {
                const storedAppearance = localStorage.getItem('appearance') || 'system';
                if (storedAppearance === 'dark' || (storedAppearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        @vite(['resources/css/app.css'])

        <style>
            .font-display {
                font-family: 'Syne', system-ui, -apple-system, sans-serif;
            }
            .font-body {
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            }
        </style>
    </head>
    <body class="h-full bg-slate-50 text-slate-900 antialiased selection:bg-indigo-500 selection:text-white dark:bg-slate-950 dark:text-slate-100 flex items-center justify-center overflow-hidden relative select-none font-body">
        {{-- Subtle Ambient Radial Mesh (Static, Clean, No Motion) --}}
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
            <div class="absolute inset-0 [background-image:radial-gradient(rgba(99,102,241,0.06)_1px,transparent_1px)] [background-size:32px_32px] opacity-70 dark:[background-image:radial-gradient(rgba(129,140,248,0.05)_1px,transparent_1px)]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] bg-gradient-to-tr from-indigo-500/10 via-purple-500/10 to-pink-500/10 dark:from-indigo-500/15 dark:via-purple-500/15 dark:to-pink-500/15 rounded-full blur-[110px] pointer-events-none"></div>
        </div>

        {{-- Composition: Layered Overlapping Typography (Static, High Contrast, No Separate Button) --}}
        <main class="relative z-10 w-full max-w-3xl px-4 py-8 text-center flex flex-col items-center justify-center">
            
            {{-- LAYER 0 (BELAKANG): Angka Status Code dengan Warna Kontras Tinggi --}}
            <div class="relative select-none pointer-events-none z-0">
                <h1 class="font-display text-7xl sm:text-8xl md:text-9xl lg:text-[10.5rem] font-extrabold tracking-tighter leading-none text-slate-800 dark:text-slate-100 drop-shadow-sm select-none">
                    @yield('code')
                </h1>
            </div>

            {{-- LAYER 1 (DEPAN): Redaksi Kalimat yang Menutupi Sebagian Angka di Atas --}}
            <div class="relative z-10 -mt-6 sm:-mt-8 md:-mt-10 lg:-mt-12 flex flex-col items-center px-4">
                
                {{-- Penamaan Standar (Understated & Elegan) --}}
                <span class="text-[11px] sm:text-xs font-mono tracking-widest uppercase text-slate-500 dark:text-slate-400 mb-2.5 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm px-3.5 py-0.5 rounded-full border border-slate-200 dark:border-slate-800 shadow-2xs">
                    @yield('code') • @yield('standard_title')
                </span>

                {{-- Redaksi Kalimat Utama Berhuruf Syne yang Menimpa Angka --}}
                <h2 class="font-display text-xl sm:text-2xl md:text-3xl lg:text-4xl font-extrabold uppercase tracking-tight text-slate-900 dark:text-white max-w-xl leading-tight drop-shadow-xs">
                    @yield('punchy_message')
                </h2>

                {{-- Redaksi Kalimat dengan Tombol "KEMBALI" yang Menyatu di Dalamnya --}}
                <p class="mt-4 text-sm sm:text-base md:text-lg font-medium text-slate-600 dark:text-slate-300 max-w-xl mx-auto leading-relaxed flex flex-wrap items-center justify-center gap-2">
                    <span>@yield('action_prefix', 'Mari saya pandu')</span>
                    <button
                        type="button"
                        onclick="window.history.length > 1 ? window.history.back() : window.location.href = '{{ url('/') }}'"
                        class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-1.5 text-xs sm:text-sm font-bold tracking-wide uppercase shadow-md hover:bg-slate-800 dark:hover:bg-slate-100 hover:scale-105 active:scale-95 transition-all duration-200 cursor-pointer select-none"
                    >
                        <span>@yield('action_button_text', 'KEMBALI KE JALAN YANG BENAR')</span>
                        <svg class="size-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </button>
                </p>

            </div>
        </main>
    </body>
</html>
