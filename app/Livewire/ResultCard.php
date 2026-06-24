<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\RegionLevel;
use App\Enums\Sex;
use App\Models\ScoreCache;
use App\Services\SingleScoreService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ResultCard extends Component
{
    public string $level = 'kommun';

    public string $sex = 'female';

    public int $ageMin = 25;

    public int $ageMax = 35;

    public function mount(): void
    {
        $this->level = request()->query('level', $this->level);
        $this->sex = request()->query('sex', $this->sex);
        $this->ageMin = (int) request()->query('ageMin', $this->ageMin);
        $this->ageMax = (int) request()->query('ageMax', $this->ageMax);
    }

    #[On('target-changed')]
    public function onTargetChanged(string $level, string $sex, int $ageMin, int $ageMax): void
    {
        $this->level = $level;
        $this->sex = $sex;
        $this->ageMin = $ageMin;
        $this->ageMax = $ageMax;
    }

    public function isDesoMode(): bool
    {
        return $this->level === 'deso';
    }

    #[Computed]
    public function topResult(): ?ScoreCache
    {
        if ($this->isDesoMode()) {
            return ScoreCache::with('region')
                ->whereHas('region', fn ($q) => $q->where('level', RegionLevel::Deso->value))
                ->where('sex', Sex::All->value)
                ->where('age_min', 0)
                ->where('age_max', 0)
                ->orderByDesc('score')
                ->first();
        }

        $sex = Sex::tryFrom($this->sex) ?? Sex::Female;

        return app(SingleScoreService::class)
            ->getTopRegions($sex, $this->ageMin, $this->ageMax, 1)
            ->first();
    }

    public function sexLabel(): string
    {
        return Sex::tryFrom($this->sex)?->label() ?? 'Alla';
    }

    public function render(): View
    {
        return view('livewire.result-card');
    }
}
