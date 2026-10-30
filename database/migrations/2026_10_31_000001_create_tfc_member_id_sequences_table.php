<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tfc_member_id_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 10)->default('TFC')->unique();
            $table->unsignedBigInteger('last_number')->default(10000);
            $table->timestamps();
        });

        DB::table('tfc_member_id_sequences')->insert([
            'prefix' => 'TFC',
            'last_number' => 10000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tfc_member_id_sequences');
    }
};
