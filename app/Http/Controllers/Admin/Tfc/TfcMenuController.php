<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcMenuController extends Controller
{
    public function index(): View
    {
        $menus = TfcMenu::orderBy('location')->orderBy('sort_order')->get()->groupBy('location');
        return view('admin.tfc.menus.index', compact('menus'));
    }

    public function create(): View
    {
        $parents = TfcMenu::whereNull('parent_id')->get();
        return view('admin.tfc.menus.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:255'],
            'location' => ['required', 'in:header,footer_col1,footer_col2,footer_legal'],
            'parent_id' => ['nullable', 'exists:tfc_menus,id'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'open_new_tab' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['open_new_tab'] = $request->has('open_new_tab');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        TfcMenu::create($validated);

        return redirect()->route('admin.tfc.menus.index')->with('success', 'Navigation item added successfully.');
    }

    public function edit(TfcMenu $menu): View
    {
        $parents = TfcMenu::whereNull('parent_id')->where('id', '!=', $menu->id)->get();
        return view('admin.tfc.menus.edit', compact('menu', 'parents'));
    }

    public function update(Request $request, TfcMenu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:255'],
            'location' => ['required', 'in:header,footer_col1,footer_col2,footer_legal'],
            'parent_id' => ['nullable', 'exists:tfc_menus,id'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'open_new_tab' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['open_new_tab'] = $request->has('open_new_tab');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $menu->update($validated);

        return redirect()->route('admin.tfc.menus.index')->with('success', 'Navigation item updated successfully.');
    }

    public function destroy(TfcMenu $menu): RedirectResponse
    {
        $menu->delete();
        return redirect()->route('admin.tfc.menus.index')->with('success', 'Navigation item deleted successfully.');
    }
}
