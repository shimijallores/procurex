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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('name');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('fund_id');
        });

        Schema::create('work_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('file_url');
            $table->timestamps();

            $table->index('project_id');
        });

        Schema::create('work_program_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_program_id')->constrained('work_programs')->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('quantity', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->unsignedInteger('row_order')->default(0);
            $table->timestamps();

            $table->index('work_program_id');
            $table->index(['work_program_id', 'row_order']);
            $table->index('item_name');
        });

        Schema::create('project_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('file_url');
            $table->timestamps();

            $table->index('project_id');
        });

        Schema::create('project_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('file_url');
            $table->timestamps();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_proposals');
        Schema::dropIfExists('project_briefs');
        Schema::dropIfExists('work_program_items');
        Schema::dropIfExists('work_programs');
        Schema::dropIfExists('projects');
    }
};
