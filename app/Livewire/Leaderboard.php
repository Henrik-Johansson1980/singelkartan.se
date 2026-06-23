<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Sex;
use App\Models\ScoreCache;
use App\Services\SingleScoreService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class Leaderboard extends Component
{
    public string $sex = 'female';

    public int $ageMin = 25;

    public int $ageMax = 34;

    public bool $isLoading = false;

    #[On('target-changed')]
    public function onTargetChanged(string $sex, int $ageMin, int $ageMax): void
    {
        $this->sex = $sex;
        $this->ageMin = $ageMin;
        $this->ageMax = $ageMax;
    }

    /** @return Collection<int, ScoreCache> */
    public function topRegions(): Collection
    {
        $sex = Sex::tryFrom($this->sex) ?? Sex::Female;

        $service = app(SingleScoreService::class);

        $cached = $service->getTopRegions($sex, $this->ageMin, $this->ageMax, 10);

        if ($cached->isNotEmpty()) {
            return $cached;
        }

        return $service->computeTopRegions($sex, $this->ageMin, $this->ageMax, 10);
    }

    public function sexLabel(): string
    {
        return Sex::tryFrom($this->sex)?->label() ?? 'Alla';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.leaderboard', [
            'topRegions' => $this->topRegions(),
        ]);
    }
}
