<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PostalLookup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostalLookup>
 */
class PostalLookupFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'postal_code' => $this->faker->unique()->numerify('#####'),
            'region_code' => $this->faker->numerify('####'),
        ];
    }
}
