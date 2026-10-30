<?php

namespace App\Services\Tfc;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TfcMemberIdService
{
    /**
     * Atomically generate a unique, sequential TFC Member ID.
     * Uses row-level lock on the sequences table to guarantee concurrency safety.
     *
     * Example outputs: TFC10001, TFC10002, ...
     */
    public function generateNextMemberId(string $prefix = 'TFC'): string
    {
        return DB::transaction(function () use ($prefix) {
            $seq = DB::table('tfc_member_id_sequences')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                DB::table('tfc_member_id_sequences')->insert([
                    'prefix' => $prefix,
                    'last_number' => 10000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $seq = DB::table('tfc_member_id_sequences')
                    ->where('prefix', $prefix)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $seq) {
                throw new RuntimeException("Could not acquire sequence lock for prefix: {$prefix}");
            }

            $nextNumber = $seq->last_number + 1;

            DB::table('tfc_member_id_sequences')
                ->where('id', $seq->id)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return $prefix . $nextNumber;
        });
    }

    /**
     * Check if a Member ID string already exists in the system.
     */
    public function exists(string $memberId): bool
    {
        return DB::table('direct_selling_members')
            ->where('member_id', trim($memberId))
            ->exists();
    }
}
