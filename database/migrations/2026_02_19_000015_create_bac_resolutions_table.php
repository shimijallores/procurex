<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bac_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aoq_id')->nullable()->constrained('aoqs')->nullOnDelete();
            $table->string('resolution_no')->unique();
            $table->date('resolution_date');
            $table->date('meeting_date')->nullable();
            $table->string('project_name');
            $table->string('winner_supplier_name');
            $table->decimal('winner_amount', 15, 2)->default(0);
            $table->string('calculation_label')->default('Lowest Calculated');
            $table->text('justification')->nullable();
            $table->string('signatory_chairperson')->nullable();
            $table->string('signatory_member_one')->nullable();
            $table->string('signatory_member_two')->nullable();
            $table->string('signatory_member_three')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index('resolution_no', 'idx_bac_resolution_no');
            $table->index('resolution_date', 'idx_bac_resolution_date');
            $table->index('meeting_date', 'idx_bac_meeting_date');
            $table->index('finalized_at', 'idx_bac_finalized_at');
            $table->index('aoq_id', 'idx_bac_primary_aoq_id');
        });

        Schema::create('bac_resolution_aoq', function (Blueprint $table) {
            $table->foreignId('bac_resolution_id')->constrained('bac_resolutions')->cascadeOnDelete();
            $table->foreignId('aoq_id')->constrained('aoqs')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['bac_resolution_id', 'aoq_id'], 'pk_bac_resolution_aoq');
            $table->unique('aoq_id', 'uq_bac_resolution_aoq_batch');
            $table->index(['bac_resolution_id', 'sort_order'], 'idx_bac_resolution_aoq_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bac_resolution_aoq');
        Schema::dropIfExists('bac_resolutions');
    }
};
