<!DOCTYPE html>
<html lang="sv">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Singeldensitet' }}</title>
        <meta name="description" content="{{ $description ?? 'Singeltätaste kommunerna och DeSO-områdena i Sverige.' }}">

        {{-- No Singelkartan OG tags in white-label — host embeds their own --}}

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

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

        {{ $head ?? '' }}
    </head>
    <body class="min-h-screen bg-white dark:bg-surface-900 text-surface-900 dark:text-surface-100">

        {{-- Custom logo slot — white-label integrators replace the default --}}
        <header class="px-6 py-4 border-b border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-900">
            {{ $logo ?? '' }}
        </header>

        <main class="max-w-5xl mx-auto px-4 py-10 space-y-6">
            <livewire:target-form />
            <livewire:leaderboard />
            <livewire:result-card />
        </main>

        {{-- No Singelkartan footer branding --}}
        <footer class="text-center py-6 text-xs text-surface-600 dark:text-surface-400">
            <p>Data: <a href="https://www.scb.se" target="_blank" rel="noopener" class="underline hover:text-surface-600 dark:hover:text-surface-300">SCB</a> · CC0 1.0</p>
        </footer>

        @livewireScripts

        {{ $scripts ?? '' }}
    </body>
</html>
