<?php

namespace App\Http\Controllers;

use App\Dadi\Exceptions\DadiOwnershipException;
use App\Dadi\Language\DadiLanguage;
use App\Models\DadiConversation;
use App\Services\Dadi\ConversationLockedException;
use App\Services\Dadi\DadiAttributionService;
use App\Services\Dadi\DadiTurnResult;
use App\Services\DadiMessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * The two customer-facing surfaces of Dadi.
 *
 *   show   the premium conversational page (restores the latest owned
 *          conversation, never calls the AI on page load, never starts one)
 *   message  one customer turn -> the UI-safe JSON contract
 *   trackClick  an attribution-only beacon for recommendation clicks; it is
 *          purely additive and validates ownership before recording anything
 *
 * The controller stays thin: every decision lives in DadiMessagingService or
 * the Stage 1-7 domain. It only validates the raw input, maps exceptions to a
 * customer-safe status, and shapes the response.
 */
class DadiController extends Controller
{
    public function __construct(
        private readonly DadiMessagingService $dadi,
        private readonly DadiAttributionService $dadiAttribution,
    ) {}

    public function show(Request $request)
    {
        $user = auth('web')->user();
        $sessionId = $request->session()->getId();

        $conversation = $this->dadi->currentForCast($user, $sessionId);

        // The empty-state welcome is UI-only: after onboarding the stored
        // preference is already in state, so the page greets the customer in
        // their chosen language without ever persisting a greeting message.
        $preferredLanguage = $conversation instanceof DadiConversation
            ? $conversation->state->preferredLanguage
            : null;

        return view('storefront.dadi.index', [
            'conversation' => $conversation,
            'stream' => $conversation instanceof DadiConversation
                ? $this->dadi->renderStream($conversation)
                : [],
            'isLocked' => $conversation instanceof DadiConversation && ! $conversation->isActive(),
            'messageMaxLength' => (int) config('dadi.ui.message_max_length', 1000),
            'welcomeTitle' => $preferredLanguage !== null
                ? DadiLanguage::welcomeTitleFor($preferredLanguage)
                : null,
        ]);
    }

    /**
     * The validated onboarding step: the customer picks their conversation
     * language (english | hindi | hinglish, or "other" with a free-text name)
     * and Laravel persists that preference onto the existing ConversationState.
     *
     * This is the ONLY place a language preference is ever created. The AI has
     * no write path to it (AiOutputValidator drops reserved keys), and no
     * greeting is persisted here — the reloaded page reveals the conversation
     * with a UI-only welcome, and the first customer message stays the first
     * real turn. A safety-held conversation can never be resumed for this.
     */
    public function onboard(Request $request): JsonResponse
    {
        $data = $request->validate([
            'language' => ['required', 'string', Rule::in(DadiLanguage::SUPPORTED)],
            'language_name' => [
                'nullable',
                'required_if:language,other',
                'filled',
                'string',
                'max:'.(int) config('dadi.language.max_name_length', 40),
                'not_regex:/[\x00-\x1F\x7F]/',
            ],
        ]);

        $user = auth('web')->user();
        $sessionId = $request->session()->getId();

        try {
            $conversation = $this->dadi->onboard(
                user: $user,
                sessionId: $sessionId,
                preferred: (string) $data['language'],
                name: isset($data['language_name']) ? (string) $data['language_name'] : null,
            );
        } catch (ConversationLockedException $e) {
            return response()->json([
                'error' => 'conversation_locked',
                'message' => 'Yeh baatein band ho chuki hain beta — Dadi idhar aur guidance nahi de sakti.',
            ], 409);
        } catch (\Throwable $e) {
            Log::error('Dadi onboarding failed.', [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
            ]);

            return response()->json([
                'error' => 'dadi_unavailable',
                'message' => 'Beta, Dadi abhi kuch der ke liye vyast hain. Thodi der baad phir se try karein.',
            ], 500);
        }

        return response()->json([
            'conversation' => [
                'id' => (int) $conversation->getKey(),
                'status' => $conversation->status,
            ],
            'language' => [
                'code' => $conversation->state->preferredLanguage,
                'name' => $conversation->state->preferredLanguageName,
            ],
        ]);
    }

    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:'.(int) config('dadi.ui.message_max_length', 1000)],
            'conversation_id' => ['nullable', 'integer'],
            'locale' => ['nullable', 'string', 'max:5', Rule::in(['en', 'hi'])],
        ]);

        // A whitespace-only message is not a real turn: reject it before it
        // reaches the AI pipeline so it never produces a 500 or a useless turn.
        if (trim((string) $data['message']) === '') {
            return response()->json([
                'error' => 'message_empty',
                'message' => 'Beta, khali sandesh nahi bheja jaa sakta. Kuch likhiye.',
            ], 422);
        }

        $user = auth('web')->user();
        $sessionId = $request->session()->getId();

        // Spend cap: without a login gate, these daily counters are the guard
        // against unbounded AI consumption from anonymous sessions.
        if ($this->usageOverLimit($user, $sessionId)) {
            return response()->json([
                'error' => 'dadi_limit_reached',
                'message' => 'Beta, aaj ke liye Dadi ke saath itni hi baatein ho gayi hain. Kal phir se aayen!',
            ], 429);
        }

        try {
            $outcome = $this->dadi->turn(
                user: $user,
                sessionId: $sessionId,
                message: $data['message'],
                locale: (string) ($data['locale'] ?? 'en'),
                conversationId: isset($data['conversation_id']) ? (int) $data['conversation_id'] : null,
            );
        } catch (ConversationLockedException $e) {
            return response()->json([
                'error' => 'conversation_locked',
                'message' => 'Yeh baatein band ho chuki hain beta — Dadi idhar aur guidance nahi de sakti.',
            ], 409);
        } catch (DadiOwnershipException $e) {
            return response()->json([
                'error' => 'conversation_not_found',
                'message' => 'Beta, yeh baatchit nahi mili. Page dobaara khol lein.',
            ], 403);
        } catch (\Throwable $e) {
            // Catch-all: never leak internals or raw stack traces to the
            // customer. Log for operators without the message content.
            Log::error('Dadi turn failed.', [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
                'conversation_id' => isset($data['conversation_id']) ? (int) $data['conversation_id'] : null,
                'user_id' => $user?->id,
            ]);

            return response()->json([
                'error' => 'dadi_unavailable',
                'message' => 'Beta, Dadi abhi kuch der ke liye vyast hain. Thodi der baad phir se try karein.',
            ], 500);
        }

        $this->recordTurn($user, $sessionId);

        return response()->json($this->payload($outcome));
    }

    public function trackClick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
        ]);

        $user = auth('web')->user();
        $sessionId = $request->session()->getId();

        try {
            $this->dadiAttribution->record(
                action: 'click',
                user: $user,
                sessionId: $sessionId,
                reference: (string) $validated['reference'],
            );
        } catch (\Throwable) {
            // Best-effort; clicks are attribution only.
        }

        return response()->json(['tracked' => true]);
    }

    /**
     * The most specific budget scope wins: a signed-in user is capped by their
     * own counter, an anonymous visitor by their session counter, and a raw IP
     * backstop guards against session-cycling guests.
     */
    private function usageScopes(mixed $user, string $sessionId): array
    {
        if (! (bool) config('dadi.usage.enabled', true)) {
            return [];
        }

        $day = now()->toDateString();

        if ($user !== null) {
            return [
                'dadi.usage.daily_limit_authenticated' => "dadi-usage:{$day}:user:{$user->getKey()}",
            ];
        }

        return [
            'dadi.usage.daily_limit_session' => "dadi-usage:{$day}:session:{$sessionId}",
            'dadi.usage.daily_limit_anon_ip' => "dadi-usage:{$day}:ip:".($this->ipKey() ?? 'anon'),
        ];
    }

    private function usageOverLimit(mixed $user, string $sessionId): bool
    {
        foreach ($this->usageScopes($user, $sessionId) as $configKey => $cacheKey) {
            $limit = max(1, (int) config($configKey));
            if ((int) Cache::get($cacheKey, 0) >= $limit) {
                return true;
            }
        }

        return false;
    }

    private function recordTurn(mixed $user, string $sessionId): void
    {
        $scopes = $this->usageScopes($user, $sessionId);
        if ($scopes === []) {
            return;
        }

        $day = now()->toDateString();
        $ttl = now()->startOfDay()->addDay()->diffInSeconds(now());

        foreach ($scopes as $cacheKey) {
            Cache::add($cacheKey, 0, $ttl);
            Cache::increment($cacheKey);
        }
    }

    private function ipKey(): ?string
    {
        $ip = request()->ip();

        return $ip === null ? null : preg_replace('/[^0-9a-f.:]/i', '', $ip);
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(DadiTurnResult $outcome): array
    {
        return [
            'conversation' => [
                'id' => (int) $outcome->conversation->getKey(),
                'status' => $outcome->conversation->status,
            ],
            'message' => [
                'role' => $outcome->assistantMessage->role,
                'content' => $outcome->assistantMessage->content,
            ],
            'recommendations' => $outcome->recommendations,
            'safety' => [
                'restricted' => $outcome->restricted,
            ],
        ];
    }
}
