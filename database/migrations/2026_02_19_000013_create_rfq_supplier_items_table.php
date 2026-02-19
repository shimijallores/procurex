<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_supplier_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_supplier_id')->constrained('rfq_suppliers')->cascadeOnDelete();
            $table->foreignId('rfq_item_id')->constrained('rfq_items')->cascadeOnDelete();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['rfq_supplier_id', 'rfq_item_id'], 'uq_rfq_supplier_item_supplier_item');
            $table->index('rfq_supplier_id', 'idx_rfq_supplier_item_supplier');
            $table->index('rfq_item_id', 'idx_rfq_supplier_item_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_supplier_items');
    }
};
