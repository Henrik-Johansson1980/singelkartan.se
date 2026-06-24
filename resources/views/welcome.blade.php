<!DOCTYPE html>
<html lang="sv">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php
            $ogSex = request()->query('sex', 'female');
            $ogAgeMin = (int) request()->query('ageMin', 25);
            $ogAgeMax = (int) request()->query('ageMax', 34);
            $ogSexLabel = \App\Enums\Sex::tryFrom($ogSex)?->label() ?? 'Alla';
            $ogDescription = "Singeltätaste kommunerna för {$ogSexLabel}, {$ogAgeMin}–{$ogAgeMax} år — baserat på SCB:s statistik.";
            $ogImageUrl = route('og-image', ['sex' => $ogSex, 'ageMin' => $ogAgeMin, 'ageMax' => $ogAgeMax]);
            $ogPageUrl = url()->full();
        @endphp
        <title>Singelkartan – Var bor singlarna i Sverige?</title>
        <meta name="description" content="{{ $ogDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $ogPageUrl }}">
        <meta property="og:title" content="Singelkartan – Var bor singlarna i Sverige?">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:image" content="{{ $ogImageUrl }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Singelkartan – Var bor singlarna i Sverige?">
        <meta name="twitter:description" content="{{ $ogDescription }}">
        <meta name="twitter:image" content="{{ $ogImageUrl }}">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Prevent flash of wrong theme --}}
        <script>
            (function () {
                const theme = localStorage.getItem('theme') || 'system';
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                    if ((localStorage.getItem('theme') || 'system') === 'system') {
                        document.documentElement.classList.toggle('dark', e.matches);
                    }
                });
            })();
        </script>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @livewireStyles
    </head>
    <body class="min-h-screen bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">

        {{-- Theme toggle --}}
        <div class="fixed top-4 right-4 z-50">
            <button
                id="theme-toggle"
                title="Byt tema"
                class="p-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 shadow-sm transition-colors"
            >
                <span id="icon-system">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
                    </svg>
                </span>
                <span id="icon-light" class="hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                </span>
                <span id="icon-dark" class="hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                </span>
            </button>
        </div>

        {{-- Hero --}}
        <header class="text-center px-4 pt-16 pb-10 md:pt-24 md:pb-14">
            <div class="inline-block mb-4 animate-bounce" style="animation-duration:2s">
                <x-heroicon-o-heart class="h-12 w-12 text-rose-500" />
            </div>
            <h1 class="text-4xl md:text-6xl font-bold tracking-tight text-slate-900 dark:text-white mb-4">
                Singelkartan
            </h1>
            <p class="text-xl md:text-2xl text-rose-600 dark:text-rose-400 font-medium mb-2">
                Var bor singlarna i Sverige?
            </p>
            <p class="text-base text-slate-500 dark:text-slate-400 max-w-lg mx-auto">
                Välj din målgrupp och hitta de singeltätaste kommunerna — helt baserat på SCB:s statistik.
            </p>
        </header>

        {{-- Main content --}}
        <main class="max-w-5xl mx-auto px-4 pb-20 space-y-6">

            {{-- Target form --}}
            <livewire:target-form />

            {{-- Leaderboard --}}
            <livewire:leaderboard />

            {{-- Share card --}}
            <livewire:result-card />

        </main>

        {{-- Footer --}}
        <footer class="text-center py-8 text-sm text-slate-400 dark:text-slate-500 space-y-1">
            <p>Data: <a href="https://www.scb.se" target="_blank" rel="noopener" class="underline hover:text-slate-600 dark:hover:text-slate-300">SCB</a> · HushallT08CKM · CC0 1.0</p>
            <p>"Singel" = ensamboende person enligt SCB:s hushållsstatistik.</p>
        </footer>

        @livewireScripts

        <script>
            (function () {
                const themes = ['system', 'light', 'dark'];

                function updateIcon() {
                    const theme = localStorage.getItem('theme') || 'system';
                    document.getElementById('icon-system').classList.toggle('hidden', theme !== 'system');
                    document.getElementById('icon-light').classList.toggle('hidden', theme !== 'light');
                    document.getElementById('icon-dark').classList.toggle('hidden', theme !== 'dark');
                }

                document.getElementById('theme-toggle').addEventListener('click', function () {
                    const current = localStorage.getItem('theme') || 'system';
                    const next = themes[(themes.indexOf(current) + 1) % themes.length];
                    localStorage.setItem('theme', next);
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.classList.toggle('dark', next === 'dark' || (next === 'system' && prefersDark));
                    updateIcon();
                });

                updateIcon();
            })();
        </script>
    </body>
</html>
