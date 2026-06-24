<div>
    @if ($topRegions->isEmpty())
        <div class="rounded-2xl bg-white dark:bg-slate-800 shadow-lg shadow-slate-200/50 dark:shadow-black/20 p-8 border border-slate-200 dark:border-slate-700 text-center">
            <x-heroicon-o-magnifying-glass class="h-10 w-10 mx-auto mb-3 text-slate-400 dark:text-slate-500" />
            <p class="text-slate-600 dark:text-slate-300 font-medium">Inga data hittades för det valda filtret.</p>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Prova ett annat åldersspann eller kön.</p>
        </div>
    @else
        <div class="rounded-2xl bg-white dark:bg-slate-800 shadow-lg shadow-slate-200/50 dark:shadow-black/20 border border-slate-200 dark:border-slate-700 overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80">
                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <x-heroicon-o-heart class="h-5 w-5 text-rose-500" />
                    Topp {{ $topRegions->count() }} singeltätaste kommuner
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $this->sexLabel() }}, {{ $ageMin }}–{{ $ageMax }} år &nbsp;·&nbsp; Källa: SCB
                </p>
            </div>

            {{-- Map + List grid --}}
            <div class="flex flex-col md:flex-row">

                {{-- Choropleth map (wire:ignore keeps Livewire from touching this DOM) --}}
                <div
                    wire:ignore
                    x-data="choroplethMap"
                    data-map-data="{{ $mapDataJson }}"
                    class="md:w-80 lg:w-96 flex-shrink-0 border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-700"
                >
                    <div
                        x-ref="mapEl"
                        class="h-72 md:h-full min-h-72"
                    ></div>
                </div>

                {{-- Toplist --}}
                <div class="flex-1 min-w-0 flex flex-col">
                    <ol class="divide-y divide-slate-100 dark:divide-slate-700/50 flex-1">
                        @foreach ($topRegions as $index => $entry)
                            <li
                                wire:key="region-{{ $entry->id }}"
                                x-on:singelkartan:map-hover.window="const on = $event.detail.code === '{{ $entry->region->code }}'; $el.classList.toggle('!bg-slate-100', on); $el.classList.toggle('dark:!bg-slate-700/50', on);"
                                @mouseover="$dispatch('singelkartan:list-hover', { code: '{{ $entry->region->code }}' })"
                                @mouseout="$dispatch('singelkartan:list-hover', { code: null })"
                                class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors duration-100 group"
                            >
                                {{-- Rank badge --}}
                                <div @class([
                                    'flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $index === 0,
                                    'bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300' => $index === 1,
                                    'bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-300' => $index === 2,
                                    'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-400' => $index > 2,
                                ])>
                                    {{ $index + 1 }}
                                </div>

                                {{-- Region name --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-900 dark:text-slate-100 truncate">
                                        {{ $entry->region->name ?? 'Okänd' }}
                                    </p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">
                                        {{ number_format($entry->single_count) }} singlar av {{ number_format($entry->total_count) }} totalt
                                    </p>
                                </div>

                                {{-- Score bar --}}
                                <div class="flex-shrink-0 w-24 hidden sm:block">
                                    <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-rose-400 to-rose-500 transition-all duration-500"
                                            style="width: {{ min(100, $entry->score) }}%"
                                        ></div>
                                    </div>
                                </div>

                                {{-- Percentage --}}
                                <div class="flex-shrink-0 text-right w-14">
                                    <span class="text-base font-bold text-rose-600 dark:text-rose-400">
                                        {{ $entry->singlePercentage() }}%
                                    </span>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">singlar</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    {{-- Footer note --}}
                    <div class="px-6 py-3 bg-slate-50 dark:bg-slate-900/30 border-t border-slate-100 dark:border-slate-700/50">
                        <p class="text-xs text-slate-400 dark:text-slate-500">
                            "Singel" = ensamboende person enligt SCB:s hushållsstatistik.
                            Områden med färre än 20 i målgruppen visas inte.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading overlay --}}
    <div wire:loading.delay class="mt-4 text-center text-slate-400 dark:text-slate-500 text-sm animate-pulse">
        <x-heroicon-o-magnifying-glass class="inline h-4 w-4 mr-1" />
        Letar singlar...
    </div>
</div>

<style>
.singel-tip {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 13px;
    color: #1e293b;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    pointer-events: none;
}
.dark .singel-tip {
    background: #1e293b;
    border-color: #334155;
    color: #f1f5f9;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .4);
}
.singel-tip::before { display: none; }
</style>
