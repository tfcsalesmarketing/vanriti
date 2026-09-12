<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('shipmojo_order_id')->nullable()->after('awb_number');
            $table->string('shipmojo_reference_id')->nullable()->after('shipmojo_order_id');
            $table->string('lr_number')->nullable()->after('shipmojo_reference_id');
            $table->string('courier_service')->nullable()->after('lr_number');
            $table->timestamp('shipmojo_pushed_at')->nullable()->after('courier_service');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'shipmojo_order_id',
                'shipmojo_reference_id',
                'lr_number',
                'courier_service',
                'shipmojo_pushed_at',
            ]);
        });
    }
};
