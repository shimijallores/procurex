<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('noa_id')->constrained('noas')->cascadeOnDelete();
            $table->string('po_no')->unique();
            $table->date('po_date');
            $table->string('mode_of_procurement')->default('Small Value');
            $table->string('place_of_delivery');
            $table->smallInteger('delivery_term_days')->nullable();
            $table->string('payment_term')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('total_amount_words')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique('noa_id', 'uq_purchase_order_noa');
            $table->index('noa_id', 'idx_purchase_order_noa');
            $table->index('po_no', 'idx_purchase_order_no');
            $table->index('po_date', 'idx_purchase_order_date');
            $table->index('mode_of_procurement', 'idx_purchase_order_mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
