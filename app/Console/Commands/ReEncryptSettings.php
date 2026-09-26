<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;

class ReEncryptSettings extends Command
{
    protected $signature = 'settings:re-encrypt
        {--from-key= : The previous APP_KEY (base64) used to encrypt the current passwords. Skips entries it cannot decrypt.}';

    protected $description = 'Re-encrypt all password/secret setting values under the current APP_KEY. Run after php artisan key:generate.';

    public function handle(): int
    {
        $records = Setting::whereIn('type', ['password', 'secret'])->get();

        if ($records->isEmpty()) {
            $this->info('No password/secret settings found.');

            return self::SUCCESS;
        }

        $current = new Encrypter($this->keyBytes(env('APP_KEY')), config('app.cipher'));
        $old = $this->option('from-key')
            ? new Encrypter($this->keyBytes($this->option('from-key')), config('app.cipher'))
            : $current;

        $updated = 0;

        foreach ($records as $setting) {
            $value = $setting->value;
            if ($value === '') {
                continue;
            }
            try {
                $plain = $old->decrypt($value);
            } catch (\Throwable $e) {
                $this->warn("Skipping {$setting->key} — could not decrypt with the source key.");

                continue;
            }
            $setting->value = $current->encrypt($plain);
            $setting->save();
            $updated++;
        }

        $this->info("Re-encrypted {$updated} settings under the current APP_KEY.");

        return self::SUCCESS;
    }

    private function keyBytes(string $key): string
    {
        $key = str_replace('base64:', '', $key);

        return base64_decode($key);
    }
}