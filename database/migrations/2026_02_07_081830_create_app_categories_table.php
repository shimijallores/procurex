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
        Schema::create('app_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('pap_code')->unique();
            $table->string('name');
            $table->boolean('early_procurement')->default(false)->nullable();
            $table->string('mode_of_procurement');
            $table->unsignedTinyInteger('schedule_from_month')->nullable();
            $table->unsignedTinyInteger('schedule_to_month')->nullable();
            $table->string('source_of_fund')->nullable();
            $table->decimal('estimated_budget', 15, 2)->default(0);
            $table->decimal('mooe_amount', 15, 2)->nullable()->default(0);
            $table->decimal('co_amount', 15, 2)->nullable()->default(0);
            $table->string('remarks')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('app_id');
            $table->index('pap_code');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_categories');
    }
};
