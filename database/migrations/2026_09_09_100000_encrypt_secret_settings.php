<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('settings')
            ->whereIn('key', ['razorpay_key_secret', 'shipmojo_private_key'])
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->get();

        foreach ($rows as $row) {
            try {
                Crypt::decryptString($row->value);
                continue;
            } catch (\Throwable) {
                DB::table('settings')
                    ->where('id', $row->id)
                    ->update(['value' => Crypt::encryptString((string) $row->value)]);
            }
        }
    }

    public function down(): void
    {
        $rows = DB::table('settings')
            ->whereIn('key', ['razorpay_key_secret', 'shipmojo_private_key'])
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->get();

        foreach ($rows as $row) {
            try {
                DB::table('settings')
                    ->where('id', $row->id)
                    ->update(['value' => Crypt::decryptString($row->value)]);
            } catch (\Throwable) {
            }
        }
    }
};