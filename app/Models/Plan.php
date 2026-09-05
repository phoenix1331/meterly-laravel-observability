<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A billing tier: Free, Starter, Growth, Enterprise (seeded by PlanSeeder).
 * monthly_quota is the request allowance EnforceUsageQuota checks against;
 * price_pence feeds the revenue-rate business metric in RevenueRateCollector.
 */
#[Fillable(['name', 'slug', 'monthly_quota', 'price_pence'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'monthly_quota' => 'integer',
            'price_pence' => 'integer',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
