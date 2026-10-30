<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcSettingController extends Controller
{
    /**
     * Show TFC settings editor grouped by category.
     */
    public function index(): View
    {
        $settings = TfcSetting::all()->groupBy('group');

        return view('admin.tfc.settings.index', compact('settings'));
    }

    /**
     * Update TFC settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            $setting = TfcSetting::where('key', $key)->first();
            if ($setting) {
                $setting->update(['value' => $value]);
            } else {
                TfcSetting::create([
                    'key' => $key,
                    'value' => $value,
                    'group' => 'general',
                ]);
            }
        }

        return back()->with('success', 'TFC Sales & Marketing settings updated successfully.');
    }
}
