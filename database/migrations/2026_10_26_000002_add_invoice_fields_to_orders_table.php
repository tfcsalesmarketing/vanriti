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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'invoice_number')) {
                $table->string('invoice_number', 50)->nullable()->index();
            }
            if (! Schema::hasColumn('orders', 'invoice_url')) {
                $table->text('invoice_url')->nullable();
            }
            if (! Schema::hasColumn('orders', 'invoice_pdf_path')) {
                $table->string('invoice_pdf_path', 255)->nullable();
            }
            if (! Schema::hasColumn('orders', 'invoice_generated_at')) {
                $table->timestamp('invoice_generated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $cols = [];
            foreach (['invoice_number', 'invoice_url', 'invoice_pdf_path', 'invoice_generated_at'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $cols[] = $col;
                }
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
