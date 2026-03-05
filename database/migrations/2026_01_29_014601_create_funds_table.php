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
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_code_id')->nullable();
            $table->string('name');
            $table->enum('type', ['general', 'project']);
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'fiscal_year']);
            $table->index(['office_id', 'project_code_id', 'fiscal_year'], 'idx_funds_office_project_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funds');
    }
};
