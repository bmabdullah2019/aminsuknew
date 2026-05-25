<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentNumberService
{
    public function next(string $module, string $prefix, int $comId = 0, int $pad = 5): string
    {
        if (! Schema::hasTable('document_sequences')) {
            return $this->fallbackNumber($prefix, $pad);
        }

        return DB::transaction(function () use ($module, $prefix, $comId, $pad) {
            $sequence = DB::table('document_sequences')
                ->where('ComId', $comId)
                ->where('module', $module)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                DB::table('document_sequences')->insert([
                    'ComId' => $comId,
                    'module' => $module,
                    'prefix' => $prefix,
                    'next_number' => 1,
                    'lock_version' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sequence = DB::table('document_sequences')
                    ->where('ComId', $comId)
                    ->where('module', $module)
                    ->lockForUpdate()
                    ->first();
            }

            $nextNumber = (int) ($sequence->next_number ?? 1);

            DB::table('document_sequences')
                ->where('id', $sequence->id)
                ->update([
                    'prefix' => $prefix,
                    'next_number' => $nextNumber + 1,
                    'lock_version' => ((int) ($sequence->lock_version ?? 0)) + 1,
                    'updated_at' => now(),
                ]);

            return $prefix.str_pad((string) $nextNumber, $pad, '0', STR_PAD_LEFT);
        });
    }

    private function fallbackNumber(string $prefix, int $pad): string
    {
        return $prefix.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), $pad, '0', STR_PAD_LEFT);
    }
}
