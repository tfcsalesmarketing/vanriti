<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::where('key', $key)->value('value') ?? $default;
    }
}

if (! function_exists('secret_setting')) {
    function secret_setting(string $key, mixed $default = null): mixed
    {
        $value = setting($key, $default);

        if ($value === null) {
            return $default;
        }

        if (is_string($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }
}

if (! function_exists('store_name')) {
    function store_name(): string
    {
        return setting('store_name', config('app.name', 'VANRITI'));
    }
}

if (! function_exists('store_logo_url')) {
    function store_logo_url(): string
    {
        $logo = config('app.logo_url');

        if ($logo && (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://'))) {
            return $logo;
        }

        return asset('images/logo.webp');
    }
}

if (! function_exists('currency')) {
    function currency(): string
    {
        return setting('currency_symbol', '₹');
    }
}

if (! function_exists('format_price')) {
    function format_price(float|int|string $amount): string
    {
        return currency().' '.number_format((float) $amount, 2);
    }
}

if (! function_exists('abbreviate_number')) {
    function abbreviate_number(int|float $value): string
    {
        if ($value >= 10000000) {
            return round($value / 10000000, 2).' Cr';
        }
        if ($value >= 100000) {
            return round($value / 100000, 2).' L';
        }
        if ($value >= 1000) {
            return round($value / 1000, 1).'K';
        }

        return (string) $value;
    }
}

if (! function_exists('generate_order_number')) {
    function generate_order_number(int $id): string
    {
        return 'VAN-'.date('Y').'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('image_url')) {
    function image_url(?string $path, ?string $default = null): string
    {
        if (blank($path)) {
            return asset($default ?? 'images/placeholder.png');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk('s3')->url($path);
    }
}