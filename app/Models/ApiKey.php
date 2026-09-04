<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['tenant_id', 'name', 'prefix', 'hash', 'last_used_at', 'revoked_at'])]
#[Hidden(['hash'])]
class ApiKey extends Model
{
    /** @use HasFactory<ApiKeyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Generate a new plaintext key and its storable prefix/hash pair.
     * The plaintext value is only ever available here, at creation time.
     *
     * @return array{plaintext: string, prefix: string, hash: string}
     */
    public static function generateToken(): array
    {
        $prefix = 'mtly_'.Str::random(8);
        $secret = Str::random(40);
        $plaintext = "{$prefix}_{$secret}";

        return [
            'plaintext' => $plaintext,
            'prefix' => $prefix,
            'hash' => hash('sha256', $plaintext),
        ];
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
