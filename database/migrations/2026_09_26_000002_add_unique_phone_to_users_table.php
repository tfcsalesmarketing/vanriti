<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assign a unique database-level constraint to the phone column.
     *
     * App-level rules already treat phone as a unique login identifier, but the
     * column itself has no index, so concurrent registrations could race. Before
     * adding the constraint, any historical duplicates are kept only on the
     * earliest account and cleared from the later ones.
     */
    public function up(): void
    {
        $duplicates = DB::table('users')
            ->select('phone')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $keep = DB::table('users')
                ->where('phone', $duplicate->phone)
                ->orderBy('id')
                ->value('id');

            DB::table('users')
                ->where('phone', $duplicate->phone)
                ->where('id', '!=', $keep)
                ->update(['phone' => null]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });
    }
};