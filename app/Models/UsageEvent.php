<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UsageEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'api_key_id', 'endpoint', 'created_at'])]
class UsageEvent extends Model
{
    /** @use HasFactory<UsageEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
