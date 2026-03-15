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
        Schema::create('ppmp_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppmp_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->decimal('estimated_budget', 15, 2);
            $table->decimal('remaining_budget', 15, 2)->nullable();
            $table->timestamps();

            $table->index('ppmp_id', 'idx_ppmp_category_ppmp');
            $table->unique(['ppmp_id', 'account_id'], 'uq_ppmp_category_ppmp_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppmp_categories');
    }
};
