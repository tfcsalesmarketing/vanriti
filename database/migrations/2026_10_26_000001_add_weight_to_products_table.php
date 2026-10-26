<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'weight')) {
            if (Schema::hasColumn('products', 'net_quantity')) {
                // If net_quantity exists, copy values and rename or add weight
                Schema::table('products', function (Blueprint $table) {
                    $table->decimal('weight', 8, 2)->nullable();
                });

                if (DB::getDriverName() === 'mysql') {
                    DB::statement("
                        UPDATE products 
                        SET weight = CASE 
                            WHEN net_quantity REGEXP '^[0-9]+' THEN CAST(net_quantity AS DECIMAL(8,2))
                            ELSE 100.00
                        END
                    ");
                } else {
                    DB::table('products')->update(['weight' => 100.00]);
                }

                Schema::table('products', function (Blueprint $table) {
                    $table->dropColumn('net_quantity');
                });
            } else {
                Schema::table('products', function (Blueprint $table) {
                    $table->decimal('weight', 8, 2)->nullable();
                });
            }
        } elseif (Schema::hasColumn('products', 'net_quantity')) {
            // weight already exists; drop net_quantity to replace it with weight
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('net_quantity');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'net_quantity')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('net_quantity')->nullable();
            });
        }
    }
};
