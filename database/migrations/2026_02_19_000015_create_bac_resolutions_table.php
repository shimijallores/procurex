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
            $table->foreignId('aoq_id')->constrained('aoqs')->cascadeOnDelete();
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

            $table->unique('aoq_id', 'uq_bac_resolution_aoq');
            $table->index('resolution_no', 'idx_bac_resolution_no');
            $table->index('resolution_date', 'idx_bac_resolution_date');
            $table->index('meeting_date', 'idx_bac_meeting_date');
            $table->index('finalized_at', 'idx_bac_finalized_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bac_resolutions');
    }
};
