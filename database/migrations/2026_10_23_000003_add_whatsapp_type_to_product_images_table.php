<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE product_images MODIFY COLUMN type VARCHAR(30) NOT NULL DEFAULT 'gallery'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE product_images MODIFY COLUMN type ENUM('main', 'gallery', 'thumbnail') NOT NULL DEFAULT 'gallery'");
        }
    }
};
