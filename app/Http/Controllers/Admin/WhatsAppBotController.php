<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppBotRule;
use App\Models\WhatsAppQuickReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppBotController extends Controller
{
    /**
     * Display Bot Rules and Quick Replies management interface.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'rules');

        $rules = WhatsAppBotRule::orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $quickReplies = WhatsAppQuickReply::orderBy('category')
            ->orderBy('shortcut')
            ->get();

        return view('admin.whatsapp.bot.index', [
            'tab' => $tab,
            'rules' => $rules,
            'quickReplies' => $quickReplies,
            'totalHits' => $rules->sum('hit_count'),
            'activeRulesCount' => $rules->where('is_active', true)->count(),
            'totalReplies' => $quickReplies->count(),
        ]);
    }

    /**
     * Store a new Chatbot Auto-Responder Rule.
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

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['cooldown_minutes'] = $validated['cooldown_minutes'] ?? 30;
        $validated['priority'] = $validated['priority'] ?? 0;

        WhatsAppBotRule::create($validated);

        return redirect()->route('admin.whatsapp.bot.index', ['tab' => 'rules'])
            ->with('success', 'Chatbot auto-responder rule created successfully.');
    }

    /**
     * Update an existing Chatbot Auto-Responder Rule.
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

        $rule->update($validated);

        return redirect()->route('admin.whatsapp.bot.index', ['tab' => 'rules'])
            ->with('success', "Rule '{$rule->name}' updated successfully.");
    }

    /**
     * Toggle active status of a Bot Rule.
     */
    public function toggleRule(WhatsAppBotRule $rule): RedirectResponse
    {
        $rule->update(['is_active' => ! $rule->is_active]);

        $status = $rule->is_active ? 'enabled' : 'disabled';
        return redirect()->route('admin.whatsapp.bot.index', ['tab' => 'rules'])
            ->with('success', "Rule '{$rule->name}' has been {$status}.");
    }

    /**
     * Delete a Bot Rule.
     */
    public function destroyRule(WhatsAppBotRule $rule): RedirectResponse
    {
        $name = $rule->name;
        $rule->delete();

        return redirect()->route('admin.whatsapp.bot.index', ['tab' => 'rules'])
            ->with('success', "Rule '{$name}' deleted.");
    }

    /**
     * Store a new Quick Reply Snippet.
     */
    public function storeQuickReply(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shortcut' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:whatsapp_quick_replies,shortcut'],
            'title' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:60'],
            'content' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['category'] = $validated['category'] ?: 'General';
        $validated['is_active'] = $request->boolean('is_active', true);

        WhatsAppQuickReply::create($validated);

        return redirect()->route('admin.whatsapp.bot.index', ['tab' => 'quick_replies'])
            ->with('success', "Quick reply snippet '/{$validated['shortcut']}' saved.");
    }

    /**
     * Update an existing Quick Reply Snippet.
     */
    public function updateQuickReply(Request $request, WhatsAppQuickReply $quickReply): RedirectResponse
    {
        $validated = $request->validate([
            'shortcut' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:whatsapp_quick_replies,shortcut,' . $quickReply->id],
            'title' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:60'],
            'content' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['category'] = $validated['category'] ?: 'General';
        $validated['is_active'] = $request->boolean('is_active', true);

        $quickReply->update($validated);

        return redirect()->route('admin.whatsapp.bot.index', ['tab' => 'quick_replies'])
            ->with('success', "Quick reply '/{$quickReply->shortcut}' updated.");
    }

    /**
     * Delete a Quick Reply.
     */
    public function destroyQuickReply(WhatsAppQuickReply $quickReply): RedirectResponse
    {
        $shortcut = $quickReply->shortcut;
        $quickReply->delete();

        return redirect()->route('admin.whatsapp.bot.index', ['tab' => 'quick_replies'])
            ->with('success', "Quick reply '/{$shortcut}' deleted.");
    }

    /**
     * API endpoint returning active quick replies for Live Inbox composer autocomplete.
     */
    public function apiQuickReplies(): JsonResponse
    {
        $replies = WhatsAppQuickReply::where('is_active', true)
            ->orderBy('category')
            ->orderBy('shortcut')
            ->get(['id', 'shortcut', 'title', 'category', 'content']);

        return response()->json([
            'success' => true,
            'replies' => $replies,
        ]);
    }
}
