<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Order matters: PlanSeeder must
     * run first (Tenant and DemoSeeder both look plans up by slug),
     * DemoSeeder must run last (it deletes and replaces any existing
     * key/event rows for its two fixed tenants, so nothing after it
     * should touch those tenants).
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            TenantSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
