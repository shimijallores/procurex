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
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // These codes will be provided by the accounting office.
            $table->string('account_code')->nullable();
            $table->string('project_code')->nullable();
            $table->unsignedSmallInteger('fiscal_year');
            $table->boolean('is_addendum')->default(false);
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['office_id', 'project_id', 'fiscal_year', 'is_addendum'], 'unique_ppmp');
            $table->index(['office_id', 'project_id', 'fiscal_year'], 'idx_ppmp');
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
