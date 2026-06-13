<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aoqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table
                ->foreignId('batch_id')
                ->nullable()
                ->constrained('batches')
                ->nullOnDelete();
            $table->date('aoq_date');
            $table
                ->foreignId('winner_supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique('rfq_id', 'uq_aoq_rfq');
            $table->index('rfq_id', 'idx_aoq_rfq');
            $table->index('aoq_date', 'idx_aoq_date');
            $table->index('winner_supplier_id', 'idx_aoq_winner_supplier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aoqs');
    }
};
