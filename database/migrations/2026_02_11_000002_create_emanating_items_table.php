<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emanating_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emanating_id')->constrained('emanatings')->onDelete('cascade');
            $table->foreignId('ppmp_item_id')->constrained('ppmp_items')->onDelete('cascade');
            $table->decimal('total_price', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emanating_items');
    }
};
