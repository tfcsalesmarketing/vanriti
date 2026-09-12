<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('taxable_amount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('cgst_amount', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('sgst_amount', 12, 2)->default(0)->after('cgst_amount');
            $table->decimal('igst_amount', 12, 2)->default(0)->after('sgst_amount');
            $table->string('coupon_code')->nullable()->after('coupon_discount');
            $table->string('coupon_type')->nullable()->after('coupon_code');
            $table->decimal('coupon_value', 12, 2)->nullable()->after('coupon_type');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('taxable_amount', 12, 2)->default(0)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('taxable_amount');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'coupon_code', 'coupon_type', 'coupon_value']);
        });
    }
};