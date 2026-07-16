<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('svp_matrices', 'rfq_id')) {
            Schema::table('svp_matrices', function (Blueprint $table): void {
                $table->foreignId('rfq_id')->nullable()->constrained('rfqs')->cascadeOnDelete();
                $table->unique('rfq_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('svp_matrices', function (Blueprint $table): void {
            $table->dropUnique(['rfq_id']);
            $table->dropColumn('rfq_id');
        });
    }
};
