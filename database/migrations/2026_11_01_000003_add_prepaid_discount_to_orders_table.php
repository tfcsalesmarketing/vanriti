<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'prepaid_discount')) {
                $table->decimal('prepaid_discount', 10, 2)->default(0.00)->after('coupon_discount');
            }
        });

        $settings = [
            [
                'group' => 'payment',
                'key' => 'prepaid_discount_enabled',
                'value' => '1',
                'label' => 'Prepaid / UPI Discount Enabled',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'payment',
                'key' => 'prepaid_discount_percentage',
                'value' => '2',
                'label' => 'Prepaid Discount Percentage (%)',
                'type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'prepaid_discount')) {
                $table->dropColumn('prepaid_discount');
            }
        });

        DB::table('settings')->whereIn('key', ['prepaid_discount_enabled', 'prepaid_discount_percentage'])->delete();
    }
};
