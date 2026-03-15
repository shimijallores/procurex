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
        Schema::create('ppmp_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppmp_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('quantity');
            $table->string('unit');
            $table->decimal('estimated_budget', 15, 2);
            $table->decimal('remaining_budget', 15, 2)->nullable();
            $table->enum('mode_of_procurement', ['bidding', 'small value', 'direct', 'direct_contracting']);
            $table->timestamps();

            $table->index(['name', 'unit'], 'idx_ppmp_item_name_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppmp_items');
    }
};
