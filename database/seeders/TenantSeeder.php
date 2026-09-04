<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Skews toward cheaper plans to mirror a realistic customer base:
     * roughly half free, a third starter, and a shrinking tail of
     * growth and enterprise tenants.
     */
    public function run(): void
    {
        $mix = [
            'free' => 10,
            'starter' => 6,
            'growth' => 3,
            'enterprise' => 1,
        ];

        foreach ($mix as $slug => $count) {
            $plan = Plan::query()->where('slug', $slug)->firstOrFail();

            Tenant::factory()
                ->count($count)
                ->create(['plan_id' => $plan->id])
                ->each(function (Tenant $tenant): void {
                    $token = ApiKey::generateToken();

                    ApiKey::create([
                        'tenant_id' => $tenant->id,
                        'name' => 'default',
                        'prefix' => $token['prefix'],
                        'hash' => $token['hash'],
                    ]);
                });
        }
    }
}
