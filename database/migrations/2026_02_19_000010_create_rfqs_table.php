<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pr_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->string('svp_no')->unique();
            $table->date('rfq_date');
            $table->date('submission_deadline')->nullable();
            $table->string('project_name');
            $table->decimal('abc_amount', 15, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('pr_id', 'idx_rfq_pr');
            $table->index('rfq_date', 'idx_rfq_date');
            $table->index('submission_deadline', 'idx_rfq_submission_deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
