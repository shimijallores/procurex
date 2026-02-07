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
        Schema::create('app_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('estimated_budget', 15, 2);
            $table->decimal('mooe_amount', 15, 2)->nullable();
            $table->decimal('co_amount', 15, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('app_category_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_items');
    }
};
