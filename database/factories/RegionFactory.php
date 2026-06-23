<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RegionLevel;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->city(),
            'level' => RegionLevel::Kommun,
            'lat' => $this->faker->randomFloat(7, 55.3, 69.1),
            'lng' => $this->faker->randomFloat(7, 10.9, 24.2),
            'geojson' => null,
        ];
    }

    public function kommun(): static
    {
        return $this->state(['level' => RegionLevel::Kommun]);
    }

    public function deso(): static
    {
        return $this->state([
            'level' => RegionLevel::Deso,
            'code' => $this->faker->unique()->regexify('[0-9]{4}[A-Z][0-9]{4}'),
        ]);
    }
}
