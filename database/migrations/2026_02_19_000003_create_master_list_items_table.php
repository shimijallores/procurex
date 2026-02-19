<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_list_category_id')->constrained('master_list_categories')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('item_name');
            $table->string('unit')->nullable();
            $table->decimal('default_unit_price', 15, 2)->nullable();
            $table->boolean('is_phased_out')->default(false);
            $table->text('phased_out_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->string('search_key')->nullable();
            $table->timestamps();

            $table->unique(
                ['supplier_id', 'master_list_category_id', 'item_name', 'unit'],
                'master_list_items_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_list_items');
    }
};
