<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('earmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->onDelete('cascade');
            $table->foreignId('fund_id')->constrained('funds')->onDelete('cascade');
            $table->string('earmark_no')->unique();
            $table->date('earmark_date');
            $table->decimal('certified_amount', 15, 2);
            $table->string('expense_class')->nullable();
            $table->string('resolution_no')->nullable();
            $table->string('ordinance_no')->nullable();
            $table->date('ordinance_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('purchase_request_id');
            $table->index('fund_id');
            $table->index('earmark_date');
            $table->index('earmark_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('earmarks');
    }
};
