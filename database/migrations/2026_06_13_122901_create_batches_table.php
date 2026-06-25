<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_no', 6)->unique();
            $table->date('rfq_date')->nullable();
            $table->date('aoq_date')->nullable();
            $table->date('bac_date')->nullable();
            $table->date('noa_date')->nullable();
            $table->date('po_date')->nullable();
            $table->date('po_transmittal_date')->nullable();
            $table->date('earmark_date_from')->nullable();
            $table->date('earmark_date_to')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
