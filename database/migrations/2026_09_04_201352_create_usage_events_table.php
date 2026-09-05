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
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint');
            $table->timestamp('created_at');

            // Every read of this table filters by tenant + a date range:
            // Tenant::usageThisMonth() (the live quota check),
            // AggregateUsageEvents (the daily rollup), QuotaBurnCollector.
            // A single-column index on either alone wouldn't serve the
            // combined filter as efficiently.
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
