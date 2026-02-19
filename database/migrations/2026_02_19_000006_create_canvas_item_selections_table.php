<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvas_item_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canvas_item_id')->constrained('canvas_items')->onDelete('cascade');
            $table->foreignId('master_list_item_id')->constrained('master_list_items')->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvas_item_selections');
    }
};
