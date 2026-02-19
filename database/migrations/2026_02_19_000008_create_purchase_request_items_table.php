<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->onDelete('cascade');
            $table->foreignId('emanating_item_id')->constrained('emanating_items')->onDelete('cascade');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->boolean('vat_applicable')->default(false);
            $table->decimal('vat_rate', 5, 4)->nullable()->default(0.1200);
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Unique constraint: one item can only appear once per PR
            $table->unique(['purchase_request_id', 'emanating_item_id'], 'uq_pr_emanating_item');

            // Indexes
            $table->index('purchase_request_id');
            $table->index('emanating_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
