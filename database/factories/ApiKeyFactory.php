<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = ApiKey::generateToken();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->word().' key',
            'prefix' => $token['prefix'],
            'hash' => $token['hash'],
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }
}
