<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_transmittals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->enum('type', ['coa', 'opg']);
            $table->string('transmittal_no')->nullable();
            $table->date('transmittal_date');
            $table->text('header_text')->nullable();
            $table->string('signatory_name')->nullable();
            $table->string('signatory_title')->nullable();
            $table->string('coa_circular_no')->nullable();
            $table->timestamps();

            $table->unique(['purchase_order_id', 'type']);
            $table->index(['purchase_order_id', 'type']);
            $table->index('transmittal_date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_transmittals');
    }
};
