<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acceptance_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('air_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('acceptance_date_received')->nullable();
            $table->enum('acceptance_status', ['complete', 'partial'])->nullable();
            $table->date('inspection_date_inspected')->nullable();
            $table->text('inspection_findings_text')->nullable();
            $table->boolean('inspection_status_ok')->nullable();
            $table->string('property_officer_name')->nullable();
            $table->string('property_officer_title')->nullable();
            $table->string('inspection_officer_name')->nullable();
            $table->string('inspection_officer_title')->nullable();
            $table->timestamps();

            $table->unique('purchase_order_id');
            $table->index('acceptance_date_received');
            $table->index('inspection_date_inspected');
            $table->index('acceptance_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acceptance_inspections');
    }
};
