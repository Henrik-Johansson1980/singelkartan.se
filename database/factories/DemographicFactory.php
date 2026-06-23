<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HouseholdStatus;
use App\Enums\Sex;
use App\Models\Demographic;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Demographic>
 */
class DemographicFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $ageMin = $this->faker->randomElement([20, 25, 30, 35, 40, 45, 50, 55]);

        return [
            'region_id' => Region::factory(),
            'year' => 2025,
            'sex' => $this->faker->randomElement(Sex::cases()),
            'age_min' => $ageMin,
            'age_max' => $ageMin + 4,
            'household_status' => $this->faker->randomElement(HouseholdStatus::cases()),
            'count' => $this->faker->numberBetween(10, 5000),
        ];
    }

    public function singlePerson(): static
    {
        return $this->state(['household_status' => HouseholdStatus::SinglePerson]);
    }

    public function forSex(Sex $sex): static
    {
        return $this->state(['sex' => $sex]);
    }

    public function forAgeRange(int $ageMin, int $ageMax): static
    {
        return $this->state(['age_min' => $ageMin, 'age_max' => $ageMax]);
    }
}
