<div class="rounded-2xl bg-white dark:bg-slate-800 shadow-lg shadow-slate-200/50 dark:shadow-black/20 p-6 md:p-8 border border-slate-200 dark:border-slate-700">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
        <x-heroicon-o-heart class="h-5 w-5 text-rose-500" />
        Vem letar du efter?
    </h2>

    {{-- Sex selector --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Kön</label>
        <div class="flex gap-2 flex-wrap">
            @foreach ($this->sexOptions as $option)
                <button
                    type="button"
                    wire:click="$set('sex', '{{ $option['value'] }}')"
                    @class([
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-150 border',
                        'bg-rose-500 text-white border-rose-500 shadow-md shadow-rose-300/40 scale-105' => $sex === $option['value'],
                        'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-600 hover:border-slate-400 hover:bg-slate-50 dark:hover:bg-slate-600' => $sex !== $option['value'],
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
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
            Ålder:
            <span x-text="ageMin" class="font-bold text-rose-500 dark:text-rose-400"></span>–<span x-text="ageMax" class="font-bold text-rose-500 dark:text-rose-400"></span> år
        </label>

        <div class="relative h-6 flex items-center" x-ref="track">
            {{-- Track background --}}
            <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-2 rounded-full bg-slate-200 dark:bg-slate-600"></div>

            {{-- Active range highlight --}}
            <div
                class="absolute top-1/2 -translate-y-1/2 h-2 rounded-full bg-gradient-to-r from-rose-400 to-rose-500"
                :style="`left: ${((ageMin - min) / (max - min)) * 100}%; right: ${100 - ((ageMax - min) / (max - min)) * 100}%`"
            ></div>

            {{-- Min thumb --}}
            <input
                type="range"
                :min="min" :max="max - 1"
                x-model.number="ageMin"
                @change="if (ageMin >= ageMax) ageMin = ageMax - 1; $wire.set('ageMin', ageMin)"
                class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-2 appearance-none bg-transparent cursor-pointer pointer-events-none"
            />

            {{-- Max thumb --}}
            <input
                type="range"
                :min="min + 1" :max="max"
                x-model.number="ageMax"
                @change="if (ageMax <= ageMin) ageMax = ageMin + 1; $wire.set('ageMax', ageMax)"
                class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-2 appearance-none bg-transparent cursor-pointer pointer-events-none"
            />
        </div>

        <div class="flex justify-between text-xs text-slate-400 dark:text-slate-500 mt-1">
            <span>{{ $ageAbsMin }}</span>
            <span>{{ $ageAbsMax }}</span>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading class="mt-4 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 animate-pulse">
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
    background: #f43f5e;
    border: 2px solid white;
    box-shadow: 0 2px 6px rgba(244, 63, 94, 0.4);
    cursor: pointer;
    pointer-events: all;
}
input[type="range"]::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #f43f5e;
    border: 2px solid white;
    box-shadow: 0 2px 6px rgba(244, 63, 94, 0.4);
    cursor: pointer;
    pointer-events: all;
}
.dark input[type="range"]::-webkit-slider-thumb {
    border-color: #1e293b;
}
.dark input[type="range"]::-moz-range-thumb {
    border-color: #1e293b;
}
</style>
