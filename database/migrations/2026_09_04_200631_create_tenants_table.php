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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete, not cascade: a plan with active tenants
            // shouldn't be deletable at all. Every other foreign key in
            // this schema cascades because the child rows (usage events,
            // API keys, ...) are meaningless without their parent tenant.
            // A plan is different: tenants outlive it being editable.
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
