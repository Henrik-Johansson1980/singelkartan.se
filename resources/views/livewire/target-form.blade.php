<div class="rounded-2xl bg-white/80 dark:bg-rose-950/40 backdrop-blur shadow-lg shadow-rose-200/50 dark:shadow-rose-900/30 p-6 md:p-8 border border-rose-100 dark:border-rose-800/40">
    <h2 class="text-lg font-semibold text-rose-900 dark:text-rose-100 mb-6">Vem letar du efter? 💘</h2>

    {{-- Sex selector --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-rose-700 dark:text-rose-300 mb-2">Kön</label>
        <div class="flex gap-2 flex-wrap">
            @foreach ($this->sexOptions as $option)
                <button
                    type="button"
                    wire:click="$set('sex', '{{ $option['value'] }}')"
                    @class([
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-150 border',
                        'bg-rose-500 text-white border-rose-500 shadow-md shadow-rose-300/40 scale-105' => $sex === $option['value'],
                        'bg-white dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-700 hover:border-rose-400 hover:bg-rose-50 dark:hover:bg-rose-800/40' => $sex !== $option['value'],
                    ])
                >
                    <span>{{ $option['emoji'] }}</span>
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
        <label class="block text-sm font-medium text-rose-700 dark:text-rose-300 mb-3">
            Ålder:
            <span x-text="ageMin" class="font-bold text-rose-500"></span>–<span x-text="ageMax" class="font-bold text-rose-500"></span> år
        </label>

        <div class="relative h-6 flex items-center" x-ref="track">
            {{-- Track background --}}
            <div class="absolute inset-x-0 h-2 rounded-full bg-rose-100 dark:bg-rose-800/50"></div>

            {{-- Active range highlight --}}
            <div
                class="absolute h-2 rounded-full bg-gradient-to-r from-rose-400 to-pink-500"
                :style="`left: ${((ageMin - min) / (max - min)) * 100}%; right: ${100 - ((ageMax - min) / (max - min)) * 100}%`"
            ></div>

            {{-- Min thumb --}}
            <input
                type="range"
                :min="min" :max="max - 1"
                x-model.number="ageMin"
                @change="if (ageMin >= ageMax) ageMin = ageMax - 1; $wire.set('ageMin', ageMin)"
                class="absolute inset-0 w-full h-2 appearance-none bg-transparent cursor-pointer pointer-events-none"
            />

            {{-- Max thumb --}}
            <input
                type="range"
                :min="min + 1" :max="max"
                x-model.number="ageMax"
                @change="if (ageMax <= ageMin) ageMax = ageMin + 1; $wire.set('ageMax', ageMax)"
                class="absolute inset-0 w-full h-2 appearance-none bg-transparent cursor-pointer pointer-events-none"
            />
        </div>

        <div class="flex justify-between text-xs text-rose-400 dark:text-rose-500 mt-1">
            <span>{{ $ageAbsMin }}</span>
            <span>{{ $ageAbsMax }}</span>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading class="mt-4 flex items-center gap-2 text-sm text-rose-500 dark:text-rose-400 animate-pulse">
        <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
        Letar singlar... 🔍
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
</style>
