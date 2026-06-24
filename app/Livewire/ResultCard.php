<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Sex;
use App\Models\ScoreCache;
use App\Services\SingleScoreService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ResultCard extends Component
{
    public string $sex = 'female';

    public int $ageMin = 25;

    public int $ageMax = 34;

    public function mount(): void
    {
        $this->sex = request()->query('sex', $this->sex);
        $this->ageMin = (int) request()->query('ageMin', $this->ageMin);
        $this->ageMax = (int) request()->query('ageMax', $this->ageMax);
    }

    #[On('target-changed')]
    public function onTargetChanged(string $sex, int $ageMin, int $ageMax): void
    {
        $this->sex = $sex;
        $this->ageMin = $ageMin;
        $this->ageMax = $ageMax;
    }

    #[Computed]
    public function topResult(): ?ScoreCache
    {
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
