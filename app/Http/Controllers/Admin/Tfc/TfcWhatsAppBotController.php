<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppBotRule;
use App\Models\WhatsAppQuickReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcWhatsAppBotController extends Controller
{
    /**
     * Display TFC Bot Rules and Quick Replies management interface.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'rules');

        $rules = WhatsAppBotRule::where('channel', 'tfc')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $quickReplies = WhatsAppQuickReply::where('channel', 'tfc')
            ->orderBy('category')
            ->orderBy('shortcut')
            ->get();

        return view('admin.tfc.whatsapp.bot.index', [
            'tab' => $tab,
            'rules' => $rules,
            'quickReplies' => $quickReplies,
            'totalHits' => $rules->sum('hit_count'),
            'activeRulesCount' => $rules->where('is_active', true)->count(),
            'totalReplies' => $quickReplies->count(),
        ]);
    }

    /**
     * Store a new Chatbot Auto-Responder Rule for TFC.
     */
    public function storeRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'keywords' => ['required', 'string'],
            'match_type' => ['required', 'string', 'in:contains,exact,starts_with'],
            'action_type' => ['required', 'string', 'in:text,dynamic_order_tracking,interactive_menu,catalog,coupon'],
            'response_text' => ['nullable', 'string'],
            'cooldown_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['channel'] = 'tfc';
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['cooldown_minutes'] = $validated['cooldown_minutes'] ?? 30;
        $validated['priority'] = $validated['priority'] ?? 0;

        // Interactive buttons parsing
        if ($request->filled('button_1_title')) {
            $buttons = [];
            for ($i = 1; $i <= 3; $i++) {
                if ($request->filled("button_{$i}_title")) {
                    $buttons[] = [
                        'id' => $request->input("button_{$i}_id") ?: ("btn_tfc_" . $i),
                        'title' => $request->input("button_{$i}_title"),
                    ];
                }
            }
            $validated['buttons'] = $buttons;
        }

        WhatsAppBotRule::create($validated);

        return redirect()->route('admin.tfc.whatsapp.bot.index', ['tab' => 'rules'])
            ->with('success', 'TFC Chatbot rule created successfully.');
    }

    /**
     * Update an existing bot rule.
     */
    public function updateRule(Request $request, WhatsAppBotRule $rule): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'keywords' => ['required', 'string'],
            'match_type' => ['required', 'string', 'in:contains,exact,starts_with'],
            'action_type' => ['required', 'string', 'in:text,dynamic_order_tracking,interactive_menu,catalog,coupon'],
            'response_text' => ['nullable', 'string'],
            'cooldown_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->filled('button_1_title')) {
            $buttons = [];
            for ($i = 1; $i <= 3; $i++) {
                if ($request->filled("button_{$i}_title")) {
                    $buttons[] = [
                        'id' => $request->input("button_{$i}_id") ?: ("btn_tfc_" . $i),
                        'title' => $request->input("button_{$i}_title"),
                    ];
                }
            }
            $validated['buttons'] = $buttons;
        } else {
            $validated['buttons'] = null;
        }

        $rule->update($validated);

        return redirect()->route('admin.tfc.whatsapp.bot.index', ['tab' => 'rules'])
            ->with('success', 'Bot rule updated successfully.');
    }

    /**
     * Delete a bot rule.
     */
    public function destroyRule(WhatsAppBotRule $rule): RedirectResponse
    {
        $rule->delete();
        return redirect()->route('admin.tfc.whatsapp.bot.index', ['tab' => 'rules'])
            ->with('success', 'Rule deleted successfully.');
    }

    /**
     * Toggle active state.
     */
    public function toggleRule(WhatsAppBotRule $rule): JsonResponse
    {
        $rule->update(['is_active' => ! $rule->is_active]);
        return response()->json([
            'success' => true,
            'is_active' => $rule->is_active,
        ]);
    }

    /**
     * Store quick reply shortcut.
     */
    public function storeQuickReply(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shortcut' => ['required', 'string', 'max:50', 'alpha_dash'],
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:60'],
            'content' => ['required', 'string', 'max:4096'],
        ]);

        $validated['channel'] = 'tfc';
        $validated['is_active'] = true;

        WhatsAppQuickReply::create($validated);

        return redirect()->route('admin.tfc.whatsapp.bot.index', ['tab' => 'replies'])
            ->with('success', 'Quick reply created successfully.');
    }

    /**
     * Update quick reply.
     */
    public function updateQuickReply(Request $request, WhatsAppQuickReply $reply): RedirectResponse
    {
        $validated = $request->validate([
            'shortcut' => ['required', 'string', 'max:50', 'alpha_dash'],
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:60'],
            'content' => ['required', 'string', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $reply->update($validated);

        return redirect()->route('admin.tfc.whatsapp.bot.index', ['tab' => 'replies'])
            ->with('success', 'Quick reply updated successfully.');
    }

    /**
     * Delete quick reply.
     */
    public function destroyQuickReply(WhatsAppQuickReply $reply): RedirectResponse
    {
        $reply->delete();
        return redirect()->route('admin.tfc.whatsapp.bot.index', ['tab' => 'replies'])
            ->with('success', 'Quick reply deleted.');
    }
}
