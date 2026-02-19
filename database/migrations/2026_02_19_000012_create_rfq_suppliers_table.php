<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->boolean('is_late')->default(false);
            $table->dateTime('submitted_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['rfq_id', 'supplier_id'], 'uq_rfq_supplier_rfq_supplier');
            $table->index('rfq_id', 'idx_rfq_supplier_rfq');
            $table->index('supplier_id', 'idx_rfq_supplier_supplier');
            $table->index('is_late', 'idx_rfq_supplier_is_late');
            $table->index('submitted_at', 'idx_rfq_supplier_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_suppliers');
    }
};
