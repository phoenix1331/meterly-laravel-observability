<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\UsageEvent;
use Illuminate\Database\Seeder;

/**
 * Seeds two fixed-key tenants request.http and the "quota-exceeded"
 * demo case both depend on. Real API keys can't be seeded with a known
 * plaintext (ApiKey::generateToken() is the only place the plaintext
 * ever exists, by design). DEMO_KEY/EXHAUSTED_KEY below are hashed
 * here the same way generateToken() would, but the plaintext constant
 * itself is what request.http hard-codes as a Bearer token.
 *
 * updateOrCreate + delete-then-recreate throughout, not firstOrCreate:
 * idempotent across repeated `db:seed` runs without `migrate:fresh`,
 * and the exhausted tenant's single UsageEvent is what makes it
 * reliably over-quota on every fresh run, not just the first.
 */
class DemoSeeder extends Seeder
{
    /**
     * Fixed, documented API keys for request.http and manual testing.
     * These are not secrets: the plaintext is committed and public.
     */
    public const DEMO_KEY = 'mtly_demo0000_demo0000000000000000000000000000000001';

    public const EXHAUSTED_KEY = 'mtly_demo0001_demo0000000000000000000000000000000002';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $freePlan = Plan::query()->where('slug', 'free')->firstOrFail();

        $tinyPlan = Plan::query()->updateOrCreate(
            ['slug' => 'demo-tiny'],
            ['name' => 'Demo Tiny', 'monthly_quota' => 1, 'price_pence' => 0],
        );

        $tenant = Tenant::query()->updateOrCreate(
            ['email' => 'demo@meterly.test'],
            ['plan_id' => $freePlan->id, 'name' => 'Demo Tenant'],
        );

        ApiKey::query()->where('tenant_id', $tenant->id)->delete();

        ApiKey::create([
            'tenant_id' => $tenant->id,
            'name' => 'request.http demo key',
            'prefix' => 'mtly_demo0000',
            'hash' => hash('sha256', self::DEMO_KEY),
        ]);

        $exhaustedTenant = Tenant::query()->updateOrCreate(
            ['email' => 'demo-exhausted@meterly.test'],
            ['plan_id' => $tinyPlan->id, 'name' => 'Demo Exhausted Tenant'],
        );

        ApiKey::query()->where('tenant_id', $exhaustedTenant->id)->delete();

        $exhaustedKey = ApiKey::create([
            'tenant_id' => $exhaustedTenant->id,
            'name' => 'request.http exhausted demo key',
            'prefix' => 'mtly_demo0001',
            'hash' => hash('sha256', self::EXHAUSTED_KEY),
        ]);

        UsageEvent::query()->where('tenant_id', $exhaustedTenant->id)->delete();

        UsageEvent::create([
            'tenant_id' => $exhaustedTenant->id,
            'api_key_id' => $exhaustedKey->id,
            'endpoint' => 'api/usage',
            'created_at' => now(),
        ]);
    }
}
