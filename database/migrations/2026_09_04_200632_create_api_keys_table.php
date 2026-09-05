<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // 16, not the 12 this started as: ApiKey::generateToken()'s
            // prefix is 'mtly_' + 8 random chars = 13, and the original
            // shorter column truncated it, caught by a real insert
            // failure during testing, not designed in from the start.
            $table->string('prefix', 16)->unique();
            // 64 = sha256 hex length exactly (ApiKey::generateToken()).
            $table->string('hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
