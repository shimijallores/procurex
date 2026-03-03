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
        Schema::create('ppmps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_code_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->boolean('is_addendum')->default(false);
            $table->string('remarks')->nullable();
            $table->string('xlsx_path')->nullable();
            $table->json('budget_notices')->nullable();

            $table->timestamps();

            $table->index(['office_id', 'fiscal_year'], 'idx_ppmp');
            $table->index(['office_id', 'project_code_id', 'fiscal_year'], 'idx_ppmp_office_project_year');
            $table->unique(['office_id', 'project_code_id', 'fiscal_year'], 'uniq_ppmp_office_project_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppmps');
    }
};
