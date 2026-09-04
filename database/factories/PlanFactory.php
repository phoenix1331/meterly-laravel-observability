<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()).' Plan',
            'slug' => fn (array $attributes) => str($attributes['name'])->slug(),
            'monthly_quota' => $this->faker->randomElement([1_000, 10_000, 100_000, 1_000_000]),
            'price_pence' => $this->faker->randomElement([0, 2_900, 9_900, 29_900]),
        ];
    }
}
