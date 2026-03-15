<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bac_resolution_id')->constrained('bac_resolutions')->cascadeOnDelete();
            $table->foreignId('aoq_id')->nullable()->constrained('aoqs')->nullOnDelete();
            $table->string('noa_no')->unique();
            $table->date('noa_date');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_title')->nullable();
            $table->timestamps();

            $table->index('bac_resolution_id', 'idx_noa_bac_resolution');
            $table->unique('aoq_id', 'uq_noa_aoq');
            $table->index('aoq_id', 'idx_noa_aoq');
            $table->index('noa_date', 'idx_noa_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noas');
    }
};
