<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emanatings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_id')->constrained('funds')->onDelete('cascade');
            $table->foreignId('ppmp_id')->constrained('ppmps')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('ppmp_category_id')->constrained('ppmp_categories')->onDelete('cascade');
            $table->string('charged_to_code')->nullable();
            $table->string('pr_no')->nullable();
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->boolean('is_addendum')->default(false);
            $table->text('remarks')->nullable();
            $table->boolean('reimbursement')->default(false);
            $table->string('xlsx_path')->nullable();
            $table->string('requesting_officer_name')->nullable();
            $table->string('requesting_officer_title')->nullable();
            $table->boolean('items_match_ppmp')->default(false);
            $table->boolean('is_canvassed')->default(false)->after('items_match_ppmp');
            $table->string('status')->default('pending')->after('is_approved');
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emanatings');
    }
};
