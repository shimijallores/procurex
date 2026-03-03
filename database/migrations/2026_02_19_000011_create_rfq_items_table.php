<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignId('pr_item_id')->constrained('purchase_request_items')->cascadeOnDelete();
            $table->string('item_name');
            $table->string('unit')->nullable();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['rfq_id', 'pr_item_id'], 'uq_rfq_item_rfq_pr_item');
            $table->index('rfq_id', 'idx_rfq_item_rfq');
            $table->index('pr_item_id', 'idx_rfq_item_pr_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_items');
    }
};
