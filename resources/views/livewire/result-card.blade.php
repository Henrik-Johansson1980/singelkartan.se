<div
    x-data="{ copied: false }"
    class="rounded-2xl bg-white dark:bg-surface-800 shadow-lg shadow-surface-200/50 dark:shadow-black/20 p-5 border border-surface-200 dark:border-surface-700"
>
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">

        {{-- Summary text --}}
        <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-surface-600 dark:text-surface-400 uppercase tracking-wide mb-1">Dela resultat</p>
            @if ($this->topResult)
                <p class="text-sm font-semibold text-surface-900 dark:text-surface-100 truncate">
                    {{ $this->topResult->region?->name ?? '–' }}
                    <span class="text-brand-500 font-bold">{{ $this->topResult->singlePercentage() }}%</span>
                    singlar
                    &nbsp;·&nbsp;
                    {{ $this->sexLabel() }}, {{ $ageMin }}–{{ $ageMax }} år
                </p>
            @else
                <p class="text-sm text-surface-600 dark:text-surface-400">Inga resultat för detta urval.</p>
            @endif
        </div>

        {{-- Share buttons --}}
        <div class="flex items-center gap-2 shrink-0">

            {{-- Copy link --}}
            <button
                type="button"
                title="Kopiera länk"
                x-on:click="
                    navigator.clipboard.writeText(window.location.href);
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
                @class([
                    'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-all duration-150',
                    'bg-surface-100 dark:bg-surface-700 text-surface-600 dark:text-surface-300 border-surface-200 dark:border-surface-600 hover:bg-surface-200 dark:hover:bg-surface-600',
                ])
            >
                <span x-show="!copied">
                    <x-heroicon-o-link class="h-3.5 w-3.5" />
                </span>
                <span x-show="copied">
                    <x-heroicon-o-check class="h-3.5 w-3.5 text-green-500" />
                </span>
                <span x-text="copied ? 'Kopierad!' : 'Kopiera länk'"></span>
            </button>

            {{-- Facebook --}}
            <a
                href="#"
                title="Dela på Facebook"
                x-on:click.prevent="window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank', 'width=600,height=400')"
                class="flex items-center justify-center w-8 h-8 rounded-lg border border-surface-200 dark:border-surface-600 bg-surface-100 dark:bg-surface-700 text-surface-600 dark:text-surface-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-200 dark:hover:border-blue-800 transition-all duration-150"
            >
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                </svg>
            </a>

            {{-- X / Twitter --}}
            <a
                href="#"
                title="Dela på X"
                x-on:click.prevent="window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(window.location.href) + '&text=' + encodeURIComponent('{{ addslashes($this->sexLabel()) }}, {{ $ageMin }}–{{ $ageMax }} år — kolla singeltätheten i Sverige!'), '_blank', 'width=600,height=400')"
                class="flex items-center justify-center w-8 h-8 rounded-lg border border-surface-200 dark:border-surface-600 bg-surface-100 dark:bg-surface-700 text-surface-600 dark:text-surface-300 hover:bg-surface-200 dark:hover:bg-surface-600 hover:text-surface-900 dark:hover:text-surface-100 transition-all duration-150"
            >
                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.253 5.622 5.912-5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
            </a>

        </div>
    </div>
</div>
