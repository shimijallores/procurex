<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coa_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->text('svp_header_text')->nullable();
            $table->string('svp_salutation')->nullable();
            $table->text('bidding_header_text')->nullable();
            $table->string('bidding_salutation')->nullable();
            $table->string('signatory_name')->nullable();
            $table->string('signatory_title')->nullable();
            $table->timestamps();

            $table->unique('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coa_inspections');
    }
};
