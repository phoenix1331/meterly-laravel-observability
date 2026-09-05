<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer of the metered API. The unit everything else hangs off:
 * one plan, one or more API keys, a stream of usage events.
 *
 * usageThisMonth()/hasExceededQuota() are shared by EnforceUsageQuota
 * (blocks a request in real time) and QuotaBurnCollector (reports the
 * same ratio as a Prometheus gauge) so both read the same definition
 * of "burnt".
 */
#[Fillable(['plan_id', 'name', 'email'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function usageEvents(): HasMany
    {
        return $this->hasMany(UsageEvent::class);
    }

    public function usageThisMonth(): int
    {
        return $this->usageEvents()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function hasExceededQuota(): bool
    {
        return $this->usageThisMonth() >= $this->plan->monthly_quota;
    }
}
