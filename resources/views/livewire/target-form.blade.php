<div class="rounded-2xl bg-white dark:bg-surface-800 shadow-lg shadow-surface-200/50 dark:shadow-black/20 p-4 md:p-6 border border-surface-200 dark:border-surface-700">
    <h2 class="text-lg font-semibold text-surface-900 dark:text-surface-100 mb-6 flex items-center gap-2">
        <x-heroicon-o-heart class="h-5 w-5 text-brand-500" />
        Vem letar du efter?
    </h2>

    {{-- Level toggle --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">Kartans detaljnivå</label>
        <div class="grid grid-cols-2 gap-2">
            <button
                type="button"
                wire:click="setLevel('kommun')"
                @class([
                    'flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-150 border',
                    'bg-brand-600 text-white border-brand-600' => $level === 'kommun',
                    'bg-white dark:bg-surface-700 text-surface-700 dark:text-surface-200 border-surface-200 dark:border-surface-600 hover:border-surface-400 hover:bg-surface-50 dark:hover:bg-surface-600' => $level !== 'kommun',
                ])
            >
                <x-heroicon-o-map class="h-4 w-4" />
                Kommuner
            </button>
            <button
                type="button"
                wire:click="setLevel('deso')"
                @class([
                    'flex flex-1 items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-150 border',
                    'bg-brand-600 text-white border-brand-600' => $level === 'deso',
                    'bg-white dark:bg-surface-700 text-surface-700 dark:text-surface-200 border-surface-200 dark:border-surface-600 hover:border-surface-400 hover:bg-surface-50 dark:hover:bg-surface-600' => $level !== 'deso',
                ])
            >
                <x-heroicon-o-squares-2x2 class="h-4 w-4" />
                DeSO-områden
            </button>
        </div>

        @if ($level === 'deso')
            <p class="mt-2 text-xs text-surface-600 dark:text-surface-400">
                DeSO-data saknar köns- och åldersuppdelning — visar total singelandel per område.
            </p>
        @endif
    </div>

    {{-- Region search --}}
    <div x-data="{ search: '' }" class="mb-6">
        <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
            Sök {{ $level === 'deso' ? 'DeSO-område' : 'kommun' }}
        </label>
        <div class="relative">
            <x-heroicon-o-magnifying-glass class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-surface-400 pointer-events-none" />
            <input
                type="search"
                x-model="search"
                @input="$dispatch('singelkartan:search', { query: search })"
                placeholder="{{ $level === 'deso' ? 'T.ex. Södermalm' : 'T.ex. Stockholm' }}…"
                class="w-full rounded-xl border border-surface-200 dark:border-surface-600 bg-white dark:bg-surface-700 pl-8 pr-8 py-2 text-sm text-surface-900 dark:text-surface-100 placeholder:text-surface-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
            <button
                x-show="search !== ''"
                @click="search = ''; $dispatch('singelkartan:search', { query: '' })"
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-surface-400 hover:text-surface-600"
            >
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
    </div>

    {{-- Sex + age filters (hidden in DeSO mode) --}}
    @if ($level === 'kommun')
        {{-- Sex selector --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">Kön</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach ($this->sexOptions as $option)
                    <button
                        type="button"
                        wire:click="$set('sex', '{{ $option['value'] }}')"
                        @class([
                            'flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-150 border',
                            'bg-brand-600 text-white border-brand-600' => $sex === $option['value'],
                            'bg-white dark:bg-surface-700 text-surface-700 dark:text-surface-200 border-surface-200 dark:border-surface-600 hover:border-surface-400 hover:bg-surface-50 dark:hover:bg-surface-600' => $sex !== $option['value'],
                        ])
                    >
                        @if ($option['value'] === 'female')
                            <span class="text-base leading-none">♀</span>
                        @elseif ($option['value'] === 'male')
                            <span class="text-base leading-none">♂</span>
                        @else
                            <x-heroicon-o-users class="h-4 w-4" />
                        @endif
                        <span>{{ $option['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Age range slider --}}
        <div
            x-data="{
                ageMin: $wire.entangle('ageMin'),
                ageMax: $wire.entangle('ageMax'),
                min: {{ $ageAbsMin }},
                max: {{ $ageAbsMax }},
            }"
            class="mb-2"
        >
            <label class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-3">
                Ålder:
                <span x-text="ageMin" class="font-bold text-brand-500 dark:text-brand-400"></span>–<span x-text="ageMax" class="font-bold text-brand-500 dark:text-brand-400"></span> år
            </label>

            <div class="relative h-6 flex items-center" x-ref="track">
                {{-- Track background --}}
                <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-2 rounded-full bg-surface-200 dark:bg-surface-600"></div>

                {{-- Active range highlight --}}
                <div
                    class="absolute top-1/2 -translate-y-1/2 h-2 rounded-full bg-gradient-to-r from-brand-400 to-brand-500 left-[var(--left)] right-[var(--right)]"
                    :style="`--left: ${((ageMin - min) / (max - min)) * 100}%; --right: ${100 - ((ageMax - min) / (max - min)) * 100}%`"
                ></div>

                {{-- Min thumb --}}
                <input
                    type="range"
                    :min="min" :max="max - 5"
                    step="5"
                    x-model.number="ageMin"
                    @change="if (ageMin >= ageMax) ageMin = ageMax - 5; $wire.set('ageMin', ageMin)"
                    class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-2 appearance-none bg-transparent cursor-pointer pointer-events-none"
                />

                {{-- Max thumb --}}
                <input
                    type="range"
                    :min="min + 5" :max="max"
                    step="5"
                    x-model.number="ageMax"
                    @change="if (ageMax <= ageMin) ageMax = ageMin + 5; $wire.set('ageMax', ageMax)"
                    class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-2 appearance-none bg-transparent cursor-pointer pointer-events-none"
                />
            </div>

            <div class="flex justify-between text-xs text-surface-600 dark:text-surface-400 mt-1">
                <span>{{ $ageAbsMin }}</span>
                <span>{{ $ageAbsMax }}</span>
            </div>
        </div>
    @endif

    {{-- Loading indicator --}}
    <div wire:loading class="mt-4 flex items-center gap-2 text-sm text-surface-600 dark:text-surface-300 animate-pulse">
        <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
        <x-heroicon-o-magnifying-glass class="h-4 w-4" />
        Letar singlar...
    </div>
</div>

<style>
input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-brand-500);
    border: 2px solid white;
    box-shadow: 0 2px 6px color-mix(in srgb, var(--color-brand-500) 40%, transparent);
    cursor: pointer;
    pointer-events: all;
}
input[type="range"]::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-brand-500);
    border: 2px solid white;
    box-shadow: 0 2px 6px color-mix(in srgb, var(--color-brand-500) 40%, transparent);
    cursor: pointer;
    pointer-events: all;
}
.dark input[type="range"]::-webkit-slider-thumb {
    border-color: var(--color-surface-900);
}
.dark input[type="range"]::-moz-range-thumb {
    border-color: var(--color-surface-900);
}
</style>
