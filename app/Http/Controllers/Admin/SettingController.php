<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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
            }

            if ($value !== null) {
                Setting::where('key', $setting->key)->update(['value' => $value]);
            }
        }

        Cache::forget('settings');

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
