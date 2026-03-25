<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('svp_matrices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('office_text')->nullable();
            $table->string('po_no_text')->nullable();
            $table->string('mode_of_procurement_text')->nullable();
            $table->string('pr_no_text')->nullable();
            $table->decimal('abc_amount', 15, 2)->nullable();
            $table->string('supplier_text')->nullable();
            $table->string('particulars_text')->nullable();
            $table->decimal('amount_value', 15, 2)->nullable();
            $table->string('rfq_value')->nullable();
            $table->string('abstract_value')->nullable();
            $table->string('resolution_value')->nullable();
            $table->string('noa_po_value')->nullable();
            $table->string('transmittal_form_value')->nullable();
            $table->string('admin_value')->nullable();
            $table->string('frontdesk_value')->nullable();
            $table->text('remarks_value')->nullable();
            $table->timestamps();

            $table->unique('purchase_order_id');
            $table->index('office_text');
            $table->index('po_no_text');
            $table->index('pr_no_text');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('svp_matrices');
    }
};
