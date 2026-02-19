<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('rfq_item_id')->constrained('rfq_items')->cascadeOnDelete();
            $table->integer('quantity_snapshot');
            $table->decimal('unit_cost_snapshot', 15, 2);
            $table->decimal('amount_snapshot', 15, 2);
            $table->timestamps();

            $table->unique(['purchase_order_id', 'rfq_item_id'], 'uq_purchase_order_item_po_rfq_item');
            $table->index('purchase_order_id', 'idx_purchase_order_item_po');
            $table->index('rfq_item_id', 'idx_purchase_order_item_rfq_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
