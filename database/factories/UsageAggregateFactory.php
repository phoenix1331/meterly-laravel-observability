<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\UsageAggregate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageAggregate>
 */
class UsageAggregateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days')->format('Y-m-d'),
            'request_count' => $this->faker->numberBetween(0, 5_000),
        ];
    }
}
