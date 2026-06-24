<div class="h-full flex flex-col">
    @if ($topRegions->isEmpty())
        <div class="rounded-2xl bg-white dark:bg-surface-800 shadow-lg shadow-surface-200/50 dark:shadow-black/20 p-8 border border-surface-200 dark:border-surface-700 text-center">
            <x-heroicon-o-magnifying-glass class="h-10 w-10 mx-auto mb-3 text-surface-600 dark:text-surface-400" />
            <p class="text-surface-600 dark:text-surface-300 font-medium">Inga data hittades för det valda filtret.</p>
            <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">Prova ett annat åldersspann eller kön.</p>
        </div>
    @else
        <div class="rounded-2xl bg-white dark:bg-surface-800 shadow-lg shadow-surface-200/50 dark:shadow-black/20 border border-surface-200 dark:border-surface-700 overflow-hidden h-full flex flex-col">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/80">
                <h2 class="text-lg font-bold text-surface-900 dark:text-surface-100 flex items-center gap-2">
                    <x-heroicon-o-heart class="h-5 w-5 text-brand-500" />
                    @if ($this->isDesoMode())
                        Topp {{ $topRegions->count() }} singeltätaste DeSO-områden
                        <span x-data="{ open: false }" class="relative inline-flex items-center">
                            <button
                                @click="open = !open"
                                @click.outside="open = false"
                                type="button"
                                class="text-surface-400 hover:text-brand-500 transition-colors cursor-pointer"
                                aria-label="Vad är DeSO?"
                            >
                                <x-heroicon-o-information-circle class="h-4 w-4" />
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                class="absolute left-0 top-6 z-50 w-72 rounded-xl bg-white dark:bg-surface-800 shadow-lg border border-surface-200 dark:border-surface-700 p-4 text-sm text-surface-700 dark:text-surface-300 font-normal"
                            >
                                <p class="font-semibold text-surface-900 dark:text-surface-100 mb-1">Vad är DeSO?</p>
                                <p>DeSO (Demografiska statistikområden) är SCB:s indelning av Sverige i ~5&nbsp;700 jämförbara småområden med 700–2&nbsp;700 invånare vardera.</p>
                                <p class="mt-2 font-semibold text-surface-900 dark:text-surface-100">Vad betyder koden?</p>
                                <p class="mt-0.5">T.ex. <code class="font-mono bg-surface-100 dark:bg-surface-700 rounded px-1">1780C1321</code>:</p>
                                <ul class="mt-1 list-disc list-inside space-y-0.5">
                                    <li><code class="font-mono">1780</code> = kommunkod (Värmdö)</li>
                                    <li><code class="font-mono">C</code> = tätortstyp (A=storstad, B=stad, C=tätort, D=landsbygd)</li>
                                    <li><code class="font-mono">1321</code> = löpnummer inom kommunen</li>
                                </ul>
                            </div>
                        </span>
                    @else
                        Topp {{ $topRegions->count() }} singeltätaste kommuner
                    @endif
                </h2>
                <p class="text-sm text-surface-600 dark:text-surface-300 mt-0.5">
                    @if ($this->isDesoMode())
                        Alla &nbsp;·&nbsp; Källa: SCB TAB6568
                    @else
                        {{ $this->sexLabel() }}, {{ $ageMin }}–{{ $ageMax }} år &nbsp;·&nbsp; Källa: SCB
                    @endif
                </p>
            </div>

            {{-- Map + List grid --}}
            <div class="flex flex-col md:flex-row flex-1 min-h-0">

                {{-- Choropleth map (wire:ignore keeps Livewire from touching this DOM) --}}
                <div
                    wire:ignore
                    x-data="choroplethMap"
                    data-map-data="{{ $mapDataJson }}"
                    data-map-level="{{ $level }}"
                    class="relative md:w-3/5 shrink-0 border-b md:border-b-0 md:border-r border-surface-200 dark:border-surface-700"
                >
                    <div
                        x-ref="mapEl"
                        class="h-72 md:h-full"
                    ></div>

                    {{-- Zoom-reset button, appears after zooming into a kommun --}}
                    <button
                        x-show="isZoomed"
                        x-cloak
                        @click="resetView()"
                        type="button"
                        class="absolute top-2 right-10 z-[1000] flex items-center gap-1 rounded-lg bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-600 shadow px-2.5 py-1 text-xs font-medium text-surface-700 dark:text-surface-200 hover:bg-surface-50 dark:hover:bg-surface-700 transition-colors"
                    >
                        <x-heroicon-o-arrow-uturn-left class="h-3 w-3" />
                        Zooma ut
                    </button>
                </div>

                {{-- Toplist --}}
                <div
                    x-data="{ search: '' }"
                    @singelkartan:search.window="search = $event.detail.query"
                    class="flex-1 min-w-0 min-h-0 flex flex-col"
                >
                    <ol class="divide-y divide-surface-100 dark:divide-surface-700/50 flex-1 overflow-y-auto">
                        @foreach ($topRegions as $index => $entry)
                            <li
                                wire:key="region-{{ $entry->id }}"
                                x-show="search === '' || '{{ addslashes($entry->region->name ?? '') }}'.toLowerCase().includes(search.toLowerCase())"
                                x-on:singelkartan:map-hover.window="const on = $event.detail.code === '{{ $entry->region->code }}'; $el.classList.toggle('!bg-surface-100', on); $el.classList.toggle('dark:!bg-surface-700/50', on);"
                                x-on:singelkartan:map-select.window="const sel = $event.detail.code === '{{ $entry->region->code }}'; $el.classList.toggle('!bg-brand-50', sel); $el.classList.toggle('dark:!bg-brand-900/20', sel); $el.classList.toggle('ring-1', sel); $el.classList.toggle('ring-brand-300', sel); $el.classList.toggle('dark:ring-brand-700', sel);"
                                @mouseover="$dispatch('singelkartan:list-hover', { code: '{{ $entry->region->code }}' })"
                                @mouseout="$dispatch('singelkartan:list-hover', { code: null })"
                                @click="$dispatch('singelkartan:list-click', { code: '{{ $entry->region->code }}' })"
                                class="flex items-center gap-4 px-6 py-4 hover:bg-surface-50 dark:hover:bg-surface-700/30 transition-colors duration-100 group cursor-pointer"
                            >
                                {{-- Rank badge --}}
                                <div class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold bg-surface-100 text-surface-600 dark:bg-surface-700 dark:text-surface-300">
                                    {{ $index + 1 }}
                                </div>

                                {{-- Region name --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-surface-900 dark:text-surface-100 truncate">
                                        {{ $entry->region->name ?? 'Okänd' }}
                                    </p>
                                    <p class="text-xs text-surface-600 dark:text-surface-400">
                                        @if ($this->isDesoMode())
                                            {{ $entry->region->code ?? '' }} &nbsp;·&nbsp;
                                        @endif
                                        {{ number_format($entry->single_count) }} singlar av {{ number_format($entry->total_count) }} totalt
                                    </p>
                                </div>

                                {{-- Score bar --}}
                                <div class="shrink-0 w-24 hidden sm:block">
                                    <div class="h-2 rounded-full bg-surface-100 dark:bg-surface-700 overflow-hidden">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-500 transition-all duration-500 w-[var(--score)]"
                                            style="--score: {{ min(100, $entry->score) }}%"
                                        ></div>
                                    </div>
                                </div>

                                {{-- Percentage --}}
                                <div class="shrink-0 text-right w-14">
                                    <span class="text-base font-bold text-brand-600 dark:text-brand-400">
                                        {{ $entry->singlePercentage() }}%
                                    </span>
                                    <p class="text-xs text-surface-600 dark:text-surface-400">singlar</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    {{-- Footer note --}}
                    <div class="px-6 py-3 bg-surface-50 dark:bg-surface-900/30 border-t border-surface-100 dark:border-surface-700/50">
                        <p class="text-xs text-surface-600 dark:text-surface-400">
                            "Singel" = ensamboende person enligt SCB:s hushållsstatistik.
                            Områden med färre än 20 i målgruppen visas inte.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading overlay --}}
    <div wire:loading.delay class="mt-4 text-center text-surface-600 dark:text-surface-400 text-sm animate-pulse">
        <x-heroicon-o-magnifying-glass class="inline h-4 w-4 mr-1" />
        Letar singlar...
    </div>
</div>

<style>
.singel-tip {
    background: white;
    border: 1px solid var(--color-surface-200);
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 13px;
    color: var(--color-surface-900);
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    pointer-events: none;
}
.dark .singel-tip {
    background: var(--color-surface-900);
    border-color: var(--color-surface-700);
    color: var(--color-surface-100);
    box-shadow: 0 2px 8px rgba(0, 0, 0, .4);
}
.singel-tip::before { display: none; }
</style>
