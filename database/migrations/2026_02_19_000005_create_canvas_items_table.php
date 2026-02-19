<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvas_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canvas_id')->constrained('canvasses')->onDelete('cascade');
            $table->foreignId('emanating_item_id')->constrained('emanating_items')->onDelete('cascade');
            $table->decimal('computed_price', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['canvas_id', 'emanating_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvas_items');
    }
};
