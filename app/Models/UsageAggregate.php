<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UsageAggregateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'date', 'request_count'])]
class UsageAggregate extends Model
{
    /** @use HasFactory<UsageAggregateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'request_count' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
