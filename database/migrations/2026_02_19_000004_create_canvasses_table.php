<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvasses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emanating_id')->constrained('emanatings')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'completed', 'returned'])->default('pending');
            $table->text('return_reason')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvasses');
    }
};
