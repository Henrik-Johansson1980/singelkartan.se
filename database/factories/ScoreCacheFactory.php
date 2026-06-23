<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Sex;
use App\Models\Region;
use App\Models\ScoreCache;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ScoreCache>
 */
class ScoreCacheFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $singleCount = $this->faker->numberBetween(50, 2000);
        $totalCount = $singleCount + $this->faker->numberBetween(50, 4000);
        $ratio = $totalCount > 0 ? $singleCount / $totalCount : 0.0;

        return [
            'region_id' => Region::factory(),
            'sex' => $this->faker->randomElement([Sex::Male, Sex::Female]),
            'age_min' => 25,
            'age_max' => 34,
            'single_ratio' => round($ratio, 6),
            'single_count' => $singleCount,
            'total_count' => $totalCount,
            'score' => round($ratio * 100, 2),
            'computed_at' => Carbon::now(),
        ];
    }
}
