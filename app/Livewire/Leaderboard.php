<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\RegionLevel;
use App\Enums\Sex;
use App\Models\ScoreCache;
use App\Services\SingleScoreService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Leaderboard extends Component
{
    public string $level = 'kommun';

    public string $sex = 'female';

    public int $ageMin = 25;

    public int $ageMax = 34;

    public bool $isLoading = false;

    public string $mapDataJson = '{}';

    public function mount(): void
    {
        $this->level = request()->query('level', $this->level);
        $this->sex = request()->query('sex', $this->sex);
        $this->ageMin = (int) request()->query('ageMin', $this->ageMin);
        $this->ageMax = (int) request()->query('ageMax', $this->ageMax);

        $this->mapDataJson = $this->buildMapDataJson();
    }

    #[On('target-changed')]
    public function onTargetChanged(string $level, string $sex, int $ageMin, int $ageMax): void
    {
        $this->level = $level;
        $this->sex = $sex;
        $this->ageMin = $ageMin;
        $this->ageMax = $ageMax;

        $this->mapDataJson = $this->buildMapDataJson();
    }

    /** @return Collection<int, ScoreCache> */
    public function topRegions(): Collection
    {
        if ($this->isDesoMode()) {
            return $this->topDesoRegions();
        }

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

    public function isDesoMode(): bool
    {
        return $this->level === RegionLevel::Deso->value;
    }

    public function render(): View
    {
        return view('livewire.leaderboard', [
            'topRegions' => $this->topRegions(),
        ]);
    }

    private function buildMapDataJson(): string
    {
        if ($this->isDesoMode()) {
            return $this->buildDesoMapDataJson();
        }

        $sex = Sex::tryFrom($this->sex) ?? Sex::Female;

        $entries = ScoreCache::with('region')
            ->whereHas('region', fn ($q) => $q->where('level', RegionLevel::Kommun->value))
            ->where('sex', $sex->value)
            ->where('age_min', $this->ageMin)
            ->where('age_max', $this->ageMax)
            ->get();

        return $this->encodeMapData($entries);
    }

    private function buildDesoMapDataJson(): string
    {
        $entries = ScoreCache::with('region')
            ->whereHas('region', fn ($q) => $q->where('level', RegionLevel::Deso->value))
            ->where('sex', Sex::All->value)
            ->where('age_min', 0)
            ->where('age_max', 0)
            ->get();

        return $this->encodeMapData($entries);
    }

    /** @param Collection<int, ScoreCache> $entries */
    private function encodeMapData(Collection $entries): string
    {
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

    /** @return Collection<int, ScoreCache> */
    private function topDesoRegions(): Collection
    {
        return ScoreCache::with('region')
            ->whereHas('region', fn ($q) => $q->where('level', RegionLevel::Deso->value))
            ->where('sex', Sex::All->value)
            ->where('age_min', 0)
            ->where('age_max', 0)
            ->orderByDesc('score')
            ->limit(10)
            ->get();
    }
}
