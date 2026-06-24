<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Sex;
use App\Models\ScoreCache;
use App\Services\SingleScoreService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Leaderboard extends Component
{
    public string $sex = 'female';

    public int $ageMin = 25;

    public int $ageMax = 34;

    public bool $isLoading = false;

    public string $mapDataJson = '{}';

    public function mount(): void
    {
        $this->mapDataJson = $this->buildMapDataJson(Sex::tryFrom($this->sex) ?? Sex::Female);
    }

    #[On('target-changed')]
    public function onTargetChanged(string $sex, int $ageMin, int $ageMax): void
    {
        $this->sex = $sex;
        $this->ageMin = $ageMin;
        $this->ageMax = $ageMax;

        $sexEnum = Sex::tryFrom($sex) ?? Sex::Female;
        $service = app(SingleScoreService::class);

        if ($service->getTopRegions($sexEnum, $ageMin, $ageMax, 1)->isEmpty()) {
            $service->computeTopRegions($sexEnum, $ageMin, $ageMax);
        }

        $this->mapDataJson = $this->buildMapDataJson($sexEnum);
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

    public function render(): View
    {
        return view('livewire.leaderboard', [
            'topRegions' => $this->topRegions(),
        ]);
    }

    private function buildMapDataJson(Sex $sex): string
    {
        $entries = ScoreCache::with('region')
            ->whereHas('region')
            ->where('sex', $sex->value)
            ->where('age_min', $this->ageMin)
            ->where('age_max', $this->ageMax)
            ->get();

        $data = [];
        foreach ($entries as $entry) {
            $region = $entry->region;
            if ($region === null) {
                continue;
            }
            $data[$region->code] = [
                'name' => $region->name,
                'score' => $entry->score,
                'pct' => $entry->singlePercentage(),
                'singles' => $entry->single_count,
                'total' => $entry->total_count,
            ];
        }

        return (string) json_encode($data);
    }
}
