<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dadi-specific, human-approved product intelligence.
 *
 * This table holds ONLY what Dadi needs beyond the ordinary catalogue. The
 * authoritative product identity, SKU, price, MRP, stock, variants, category,
 * images and status remain in the existing product architecture; this table
 * references the product and stores controlled intelligence content.
 *
 * A product has at most one profile (unique product_id). Approval is a human
 * lifecycle (draft → pending_review → approved/rejected) and never confers
 * commercial availability — the live product keeps deciding that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dadi_product_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected'])->default('draft');
            $table->json('sections')->nullable();
            $table->json('concerns')->nullable();
            $table->text('positioning')->nullable();
            $table->json('approved_benefits')->nullable();
            $table->json('approved_usage_context')->nullable();
            $table->json('approved_precautions')->nullable();
            $table->json('suitability_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dadi_product_profiles');
    }
};
