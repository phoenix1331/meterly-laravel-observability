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
        Schema::create('usage_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('request_count');
            $table->timestamps();

            // Makes AggregateUsageEvents' updateOrCreate() an upsert on
            // (tenant_id, date): re-running the job for a day already
            // aggregated recomputes that one row instead of inserting a
            // duplicate.
            $table->unique(['tenant_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_aggregates');
    }
};
