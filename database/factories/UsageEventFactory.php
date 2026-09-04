<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\Tenant;
use App\Models\UsageEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageEvent>
 */
class UsageEventFactory extends Factory
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
            'api_key_id' => ApiKey::factory(),
            'endpoint' => '/api/usage',
            'created_at' => $this->faker->dateTimeBetween('-30 days'),
        ];
    }
}
