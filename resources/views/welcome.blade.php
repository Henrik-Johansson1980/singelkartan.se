<!DOCTYPE html>
<html lang="sv">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Singelkartan 💑 – Var bor singlarna i Sverige?</title>
        <meta name="description" content="Hitta de singeltätaste områdena i Sverige. Välj målgrupp och se topplistan direkt.">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @livewireStyles
    </head>
    <body class="min-h-screen bg-gradient-to-br from-rose-50 via-pink-50 to-fuchsia-50 dark:from-rose-950 dark:via-rose-900 dark:to-fuchsia-950 text-rose-900 dark:text-rose-50">

        {{-- Hero --}}
        <header class="text-center px-4 pt-16 pb-10 md:pt-24 md:pb-14">
            <div class="inline-block text-5xl mb-4 animate-bounce" style="animation-duration:2s">💑</div>
            <h1 class="text-4xl md:text-6xl font-bold tracking-tight text-rose-900 dark:text-rose-50 mb-4">
                Singelkartan
            </h1>
            <p class="text-xl md:text-2xl text-rose-600 dark:text-rose-300 font-medium mb-2">
                Var bor singlarna i Sverige?
            </p>
            <p class="text-base text-rose-500 dark:text-rose-400 max-w-lg mx-auto">
                Välj din målgrupp och hitta de singeltätaste kommunerna — helt baserat på SCB:s statistik. 📍
            </p>
        </header>

        {{-- Main content --}}
        <main class="max-w-5xl mx-auto px-4 pb-20 space-y-6">

            {{-- Target form --}}
            <livewire:target-form />

            {{-- Leaderboard --}}
            <livewire:leaderboard />

        </main>

        {{-- Footer --}}
        <footer class="text-center py-8 text-sm text-rose-400 dark:text-rose-500 space-y-1">
            <p>Data: <a href="https://www.scb.se" target="_blank" rel="noopener" class="underline hover:text-rose-600">SCB</a> · HushallT08CKM · CC0 1.0</p>
            <p>"Singel" = ensamboende person enligt SCB:s hushållsstatistik.</p>
        </footer>

        @livewireScripts
    </body>
</html>
