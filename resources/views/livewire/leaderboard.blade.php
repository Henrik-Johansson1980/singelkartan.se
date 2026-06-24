<div>
    @if ($topRegions->isEmpty())
        <div class="rounded-2xl bg-white/80 dark:bg-rose-950/40 backdrop-blur shadow-lg shadow-rose-200/50 dark:shadow-rose-900/30 p-8 border border-rose-100 dark:border-rose-800/40 text-center">
            <p class="text-4xl mb-3">🔍</p>
            <p class="text-rose-500 dark:text-rose-400 font-medium">Inga data hittades för det valda filtret.</p>
            <p class="text-sm text-rose-400 dark:text-rose-500 mt-1">Prova ett annat åldersspann eller kön.</p>
        </div>
    @else
        <div class="rounded-2xl bg-white/80 dark:bg-rose-950/40 backdrop-blur shadow-lg shadow-rose-200/50 dark:shadow-rose-900/30 border border-rose-100 dark:border-rose-800/40 overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-rose-100 dark:border-rose-800/40 bg-gradient-to-r from-rose-50 to-pink-50 dark:from-rose-900/30 dark:to-pink-900/20">
                <h2 class="text-lg font-bold text-rose-900 dark:text-rose-100">
                    💑 Topp {{ $topRegions->count() }} singeltätaste kommuner
                </h2>
                <p class="text-sm text-rose-500 dark:text-rose-400 mt-0.5">
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
                    class="md:w-80 lg:w-96 flex-shrink-0 border-b md:border-b-0 md:border-r border-rose-100 dark:border-rose-800/40"
                >
                    <div
                        x-ref="mapEl"
                        class="h-72 md:h-full min-h-72"
                    ></div>
                </div>

                {{-- Toplist --}}
                <div class="flex-1 min-w-0 flex flex-col">
                    <ol class="divide-y divide-rose-50 dark:divide-rose-800/30 flex-1">
                        @foreach ($topRegions as $index => $entry)
                            <li
                                wire:key="region-{{ $entry->id }}"
                                x-on:singelkartan:map-hover.window="const on = $event.detail.code === '{{ $entry->region->code }}'; $el.classList.toggle('!bg-rose-100/70', on); $el.classList.toggle('dark:!bg-rose-900/40', on);"
                                @mouseover="$dispatch('singelkartan:list-hover', { code: '{{ $entry->region->code }}' })"
                                @mouseout="$dispatch('singelkartan:list-hover', { code: null })"
                                class="flex items-center gap-4 px-6 py-4 hover:bg-rose-50/60 dark:hover:bg-rose-900/20 transition-colors duration-100 group"
                            >
                                {{-- Rank badge --}}
                                <div @class([
                                    'flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $index === 0,
                                    'bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300' => $index === 1,
                                    'bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-300' => $index === 2,
                                    'bg-rose-50 text-rose-400 dark:bg-rose-900/20 dark:text-rose-400' => $index > 2,
                                ])>
                                    @if ($index === 0) 🥇
                                    @elseif ($index === 1) 🥈
                                    @elseif ($index === 2) 🥉
                                    @else {{ $index + 1 }}
                                    @endif
                                </div>

                                {{-- Region name --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-rose-900 dark:text-rose-100 truncate">
                                        {{ $entry->region->name ?? 'Okänd' }}
                                    </p>
                                    <p class="text-xs text-rose-400 dark:text-rose-500">
                                        {{ number_format($entry->single_count) }} singlar av {{ number_format($entry->total_count) }} totalt
                                    </p>
                                </div>

                                {{-- Score bar --}}
                                <div class="flex-shrink-0 w-24 hidden sm:block">
                                    <div class="h-2 rounded-full bg-rose-100 dark:bg-rose-800/40 overflow-hidden">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-rose-400 to-pink-500 transition-all duration-500"
                                            style="width: {{ min(100, $entry->score) }}%"
                                        ></div>
                                    </div>
                                </div>

                                {{-- Percentage --}}
                                <div class="flex-shrink-0 text-right w-14">
                                    <span class="text-base font-bold text-rose-600 dark:text-rose-300">
                                        {{ $entry->singlePercentage() }}%
                                    </span>
                                    <p class="text-xs text-rose-400 dark:text-rose-500">singlar</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    {{-- Footer note --}}
                    <div class="px-6 py-3 bg-rose-50/50 dark:bg-rose-900/10 border-t border-rose-100 dark:border-rose-800/30">
                        <p class="text-xs text-rose-400 dark:text-rose-500">
                            "Singel" = ensamboende person enligt SCB:s hushållsstatistik.
                            Områden med färre än 20 i målgruppen visas inte.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading overlay --}}
    <div wire:loading.delay class="mt-4 text-center text-rose-400 dark:text-rose-500 text-sm animate-pulse">
        Letar singlar... 🔍
    </div>
</div>

<style>
.singel-tip {
    background: white;
    border: 1px solid #fce7f3;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 13px;
    color: #881337;
    box-shadow: 0 2px 8px rgba(244,63,94,.15);
    pointer-events: none;
}
.singel-tip::before { display: none; }
</style>
