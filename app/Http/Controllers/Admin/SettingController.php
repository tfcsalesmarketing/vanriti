<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();
        $settingsGrouped = $settings->groupBy('group');
        $groups = $settingsGrouped->keys();

        return view('admin.settings.index', compact('settingsGrouped', 'groups'));
    }

    public function update(Request $request)
    {
        $errors = [];
        $changed = [];

        foreach (Setting::all() as $setting) {
            if ($setting->type === 'boolean') {
                $value = $request->boolean($setting->key) ? '1' : '0';
            } else {
                $value = $request->input($setting->key);
            }

            if ($setting->type === 'password') {
                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                $value = Crypt::encryptString((string) $value);
            } elseif (is_string($value)) {
                $value = trim($value);

                if ($value === '') {
                    $value = null;
                }
            }

            $problem = $this->invalidSetting($setting, $value);

            if ($problem !== null) {
                $errors[$setting->key] = $problem;

                continue;
            }

            if ((string) $setting->value !== (string) $value) {
                $changed[] = $setting->key;
            }

            Setting::where('key', $setting->key)->update(['value' => $value]);
        }

        Cache::forget('settings');

        if ($changed !== []) {
            app(ActivityLogger::class)->log(
                'settings_updated',
                null,
                'Settings updated: '.implode(', ', $changed).'.',
                null,
                ['keys' => $changed],
                auth('admin')->user(),
            );
        }

        $redirect = redirect()->route('admin.settings.index');

        if ($errors !== []) {
            return $redirect->withErrors($errors)->with('error', 'Some settings were not saved. Check the highlighted fields.');
        }

        return $redirect->with('success', 'Settings updated successfully.');
    }

    protected function invalidSetting(Setting $setting, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $label = $setting->label ?: $setting->key;

        return match ($setting->type) {
            'number' => is_numeric($value) ? null : $label.' must be a number.',
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : $label.' must be an email address.',
            'url' => filter_var($value, FILTER_VALIDATE_URL) ? null : $label.' must be a valid URL.',
            'select', 'text', 'textarea', 'password' => is_string($value) && strlen($value) <= 5000
                ? null
                : $label.' is too long.',
            default => null,
        };
    }
}
