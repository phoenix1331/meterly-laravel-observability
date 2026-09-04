<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            ['name' => 'Free', 'slug' => 'free', 'monthly_quota' => 1_000, 'price_pence' => 0],
            ['name' => 'Starter', 'slug' => 'starter', 'monthly_quota' => 10_000, 'price_pence' => 2_900],
            ['name' => 'Growth', 'slug' => 'growth', 'monthly_quota' => 100_000, 'price_pence' => 9_900],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'monthly_quota' => 1_000_000, 'price_pence' => 29_900],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
