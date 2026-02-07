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
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('uploaded_file')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('fiscal_year');
            $table->index(['office_id', 'fiscal_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
