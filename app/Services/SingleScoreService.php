<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\HouseholdStatus;
use App\Enums\Sex;
use App\Models\ScoreCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SingleScoreService
{
    private readonly int $minCount;

    private readonly int $shrinkageK;

    public function __construct()
    {
        $this->minCount = (int) config('singelkartan.min_count_threshold', 20);
        $this->shrinkageK = (int) config('singelkartan.shrinkage_k', 50);
    }

    /**
     * Compute and persist scores for all regions matching the target demographic,
     * then return the top-ranked results.
     *
     * @return Collection<int, ScoreCache>
     */
    public function computeTopRegions(Sex $sex, int $ageMin, int $ageMax, int $limit = 10): Collection
    {
        $rows = $this->queryDemographics($sex, $ageMin, $ageMax);

        if ($rows->isEmpty()) {
            return collect();
        }

        $globalMeanRatio = $this->computeGlobalMean($rows);

        $scored = $rows
            ->filter(fn (\stdClass $row): bool => $row->total_count >= $this->minCount)
            ->map(fn (\stdClass $row): array => [
                'region_id' => (int) $row->region_id,
                'sex' => $sex->value,
                'age_min' => $ageMin,
                'age_max' => $ageMax,
                'single_count' => (int) $row->single_count,
                'total_count' => (int) $row->total_count,
                'single_ratio' => $row->total_count > 0
                    ? round($row->single_count / $row->total_count, 6)
                    : 0.0,
                'score' => $this->computeScore(
                    (int) $row->single_count,
                    (int) $row->total_count,
                    $globalMeanRatio,
                ),
                'computed_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ])
            ->values()
            ->all();

        if ($scored !== []) {
            ScoreCache::upsert(
                $scored,
                uniqueBy: ['region_id', 'sex', 'age_min', 'age_max'],
                update: ['single_count', 'total_count', 'single_ratio', 'score', 'computed_at', 'updated_at'],
            );
        }

        return ScoreCache::with('region')
            ->where('sex', $sex->value)
            ->where('age_min', $ageMin)
            ->where('age_max', $ageMax)
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }

    /**
     * Return top regions from the existing score cache (no recompute).
     *
     * @return Collection<int, ScoreCache>
     */
    public function getTopRegions(Sex $sex, int $ageMin, int $ageMax, int $limit = 10): Collection
    {
        return ScoreCache::with('region')
            ->where('sex', $sex->value)
            ->where('age_min', $ageMin)
            ->where('age_max', $ageMax)
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }

    /**
     * Bayesian shrinkage scoring: small populations shrink toward the global mean.
     * Normalized to 0–100.
     *
     * formula: ratio * (n / (n + K)) + globalMean * (K / (n + K))  ×  100
     */
    private function computeScore(int $singleCount, int $totalCount, float $globalMeanRatio): float
    {
        if ($totalCount === 0) {
            return 0.0;
        }

        $ratio = $singleCount / $totalCount;
        $weight = $totalCount / ($totalCount + $this->shrinkageK);
        $shrunk = $ratio * $weight + $globalMeanRatio * (1 - $weight);

        return round(min(100.0, $shrunk * 100), 2);
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     */
    private function computeGlobalMean(Collection $rows): float
    {
        $totalSingles = $rows->sum('single_count');
        $totalPopulation = $rows->sum('total_count');

        if ($totalPopulation <= 0) {
            return 0.0;
        }

        return $totalSingles / $totalPopulation;
    }

    /**
     * Query aggregated single_count and total_count per region from demographics.
     *
     * @return Collection<int, \stdClass>
     */
    private function queryDemographics(Sex $sex, int $ageMin, int $ageMax): Collection
    {
        $year = (int) config('singelkartan.sync_year');

        return DB::table('demographics')
            ->select([
                'region_id',
                DB::raw('SUM(CASE WHEN household_status = ? THEN `count` ELSE 0 END) as single_count'),
                DB::raw('SUM(`count`) as total_count'),
            ])
            ->addBinding(HouseholdStatus::SinglePerson->value, 'select')
            ->where('year', $year)
            ->where('sex', $sex->value)
            ->where('age_min', '>=', $ageMin)
            ->where('age_max', '<=', $ageMax)
            ->groupBy('region_id')
            ->get();
    }
}
