<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emanating_id')->constrained('emanatings')->onDelete('cascade');
            $table->foreignId('office_id')->constrained('offices')->onDelete('cascade');
            $table->foreignId('fund_id')->constrained('funds')->onDelete('cascade');
            $table->string('pr_no')->nullable()->index();
            $table->date('pr_date')->nullable();
            $table->string('sai_no')->nullable();
            $table->date('sai_date')->nullable();
            $table->text('purpose')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'returned', 'for_budget_review', 'approved', 'cancelled'])->default('draft')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('office_id');
            $table->index('fund_id');
            $table->index('emanating_id');
            $table->index('pr_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
