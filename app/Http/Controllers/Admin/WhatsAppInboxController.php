<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use App\Models\WhatsAppCheckoutSession;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppChatService;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsAppInboxController extends Controller
{
    public function __construct(
        protected WhatsAppChatService $chatService,
        protected WhatsAppCloudApiService $apiService
    ) {}

    /**
     * Display the WhatsApp Inbox workspace.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $filter = (string) $request->input('filter', 'all'); // all, unread, window_open, checkout_active

        $query = WhatsAppConversation::with(['latestMessage', 'user'])
            ->orderByRaw('unread_count DESC')
            ->orderBy('last_message_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('last_message_preview', 'like', "%{$search}%");
            });
        }

        if ($filter === 'unread') {
            $query->where('unread_count', '>', 0);
        } elseif ($filter === 'window_open') {
            $query->where('window_expires_at', '>', now());
        } elseif ($filter === 'checkout_active') {
            $query->whereHas('activeCheckoutSession');
        } elseif ($filter === 'mine') {
            $query->where('assigned_to', auth('admin')->id());
        } elseif ($filter === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($filter === 'resolved') {
            $query->where('status', 'resolved');
        }

        $conversations = $query->paginate(30)->withQueryString();

        // Selected conversation
        $selectedId = $request->input('conversation_id');
        $activeConversation = null;

        if ($request->filled('user_id')) {
            $user = User::find($request->input('user_id'));
            if ($user && $user->phone) {
                $activeConversation = WhatsAppConversation::findOrCreateForPhone($user->phone, $user->name);
                $selectedId = $activeConversation->id;
            }
        } elseif ($request->filled('phone')) {
            $activeConversation = WhatsAppConversation::findOrCreateForPhone($request->input('phone'));
            $selectedId = $activeConversation->id;
        }

        if ($selectedId && ! $activeConversation) {
            $activeConversation = WhatsAppConversation::with(['user', 'assignedAgent'])->find($selectedId);
        }

        if (! $activeConversation && $conversations->isNotEmpty()) {
            $activeConversation = $conversations->first();
        }

        $messages = collect();
        $customerContext = [];

        if ($activeConversation) {
            $activeConversation->markAsRead();

            $messages = $activeConversation->messages()
                ->with('sender')
                ->latest()
                ->take(60)
                ->get()
                ->reverse()
                ->values();

            $customerContext = $this->buildCustomerContext($activeConversation);
        }

        $totalUnread = WhatsAppConversation::sum('unread_count');
        $activeWindowsCount = WhatsAppConversation::where('window_expires_at', '>', now())->count();
        $isConfigured = $this->apiService->isConfigured();
        $admins = Admin::orderBy('name')->get(['id', 'name', 'email']);
        $approvedTemplates = \App\Models\WhatsAppTemplate::approved()->orderBy('name')->get();

        return view('admin.whatsapp.inbox', compact(
            'conversations',
            'activeConversation',
            'messages',
            'customerContext',
            'totalUnread',
            'activeWindowsCount',
            'isConfigured',
            'search',
            'filter',
            'admins',
            'approvedTemplates'
        ));
    }

    /**
     * Send plain text message in active conversation.
     */
    public function sendText(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:4000',
        ]);

        $res = $this->chatService->sendTextMessage(
            $conversation,
            $request->input('message'),
            auth('admin')->user()
        );

        if (! ($res['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $res['error'] ?? 'Failed to send WhatsApp message.',
                'chat_message' => $res['message'] ?? null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'chat_message' => $res['message'],
            'window_open' => $conversation->isWindowOpen(),
            'window_label' => $conversation->windowRemainingFormatted(),
        ]);
    }

    /**
     * Upload and send media attachment.
     */
    public function sendMedia(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:25600', // 25MB max
            'caption' => 'nullable|string|max:1000',
        ]);

        $res = $this->chatService->sendMediaMessage(
            $conversation,
            $request->file('file'),
            $request->input('caption'),
            auth('admin')->user()
        );

        if (! ($res['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $res['error'] ?? 'Failed to send media.',
                'chat_message' => $res['message'] ?? null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'chat_message' => $res['message'],
            'media_url' => $res['message']->getResolvedMediaUrl(),
        ]);
    }

    /**
     * Send approved Meta template to customer.
     */
    public function sendTemplate(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $request->validate([
            'template_name' => 'required|string|max:120',
            'lang_code' => 'nullable|string|max:10',
            'parameters' => 'nullable|array',
            'header_image_url' => 'nullable|url|max:500',
        ]);

        $res = $this->chatService->sendTemplateMessage(
            $conversation,
            $request->input('template_name'),
            $request->input('lang_code', 'en'),
            $request->input('parameters', []),
            $request->input('header_image_url'),
            auth('admin')->user()
        );

        if (! ($res['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $res['error'] ?? 'Failed to send template.',
                'chat_message' => $res['message'] ?? null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'chat_message' => $res['message'],
        ]);
    }

    /**
     * Lightweight AJAX polling for new messages.
     */
    public function poll(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $afterId = (int) $request->input('after_id', 0);

        $newMessages = $conversation->messages()
            ->with('sender')
            ->where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->get();

        if ($newMessages->isNotEmpty()) {
            $conversation->markAsRead();
        }

        // Fetch recent outbound message statuses so checkmarks update live
        $recentStatuses = $conversation->messages()
            ->where('direction', 'outbound')
            ->latest('id')
            ->take(25)
            ->get(['id', 'status', 'error_message']);

        return response()->json([
            'messages' => $newMessages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'direction' => $msg->direction,
                    'message_type' => $msg->message_type,
                    'body' => $msg->body,
                    'media_url' => $msg->getResolvedMediaUrl(),
                    'media_filename' => $msg->media_filename,
                    'media_mime_type' => $msg->media_mime_type,
                    'status' => $msg->status,
                    'error_message' => $msg->error_message,
                    'sender_name' => $msg->sender?->name,
                    'created_at_time' => $msg->created_at->format('h:i A'),
                    'created_at_date' => $msg->created_at->format('d M, Y'),
                ];
            }),
            'statuses' => $recentStatuses,
            'window_open' => $conversation->isWindowOpen(),
            'window_label' => $conversation->windowRemainingFormatted(),
            'unread_count' => (int) $conversation->fresh()->unread_count,
        ]);
    }

    /**
     * Poll recent conversation list summaries for real-time sidebar directory sync.
     */
    public function pollConversations(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $filter = (string) $request->input('filter', 'all');

        $query = WhatsAppConversation::with(['latestMessage'])
            ->orderByRaw('unread_count DESC')
            ->orderBy('last_message_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('last_message_preview', 'like', "%{$search}%");
            });
        }

        if ($filter === 'unread') {
            $query->where('unread_count', '>', 0);
        } elseif ($filter === 'window_open') {
            $query->where('window_expires_at', '>', now());
        } elseif ($filter === 'checkout_active') {
            $query->whereHas('activeCheckoutSession');
        } elseif ($filter === 'mine') {
            $query->where('assigned_to', auth('admin')->id());
        } elseif ($filter === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($filter === 'resolved') {
            $query->where('status', 'resolved');
        }

        $conversations = $query->take(30)->get();

        return response()->json([
            'conversations' => $conversations->map(function ($convo) {
                return [
                    'id' => $convo->id,
                    'phone' => $convo->phone,
                    'contact_name' => $convo->contact_name ?: ('+' . $convo->phone),
                    'avatar_letter' => strtoupper(substr($convo->contact_name ?: $convo->phone, 0, 1)),
                    'avatar_color' => '#' . substr(md5($convo->phone), 0, 6),
                    'last_message_preview' => $convo->last_message_preview ?: 'No messages yet',
                    'last_message_time' => $convo->last_message_at ? $convo->last_message_at->diffForHumans(null, true, true) : '',
                    'unread_count' => (int) $convo->unread_count,
                    'is_window_open' => $convo->isWindowOpen(),
                    'status' => $convo->status,
                ];
            }),
            'total_unread' => (int) WhatsAppConversation::sum('unread_count'),
            'active_windows_count' => (int) WhatsAppConversation::where('window_expires_at', '>', now())->count(),
        ]);
    }

    /**
     * Stream or proxy media file for inbox previews.
     */
    public function streamMedia(WhatsAppMessage $message): Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if ($message->media_url && Storage::disk('public')->exists($message->media_url)) {
            return response()->file(Storage::disk('public')->path($message->media_url));
        }

        // Fetch binary from Meta Graph API
        if ($message->media_id) {
            $downloadUrl = $this->apiService->getMediaDownloadUrl($message->media_id);
            if ($downloadUrl) {
                $binary = $this->apiService->downloadMediaBinary($downloadUrl);
                if ($binary) {
                    $mime = $message->media_mime_type ?: 'application/octet-stream';
                    return response($binary, 200, [
                        'Content-Type' => $mime,
                        'Content-Disposition' => 'inline; filename="' . ($message->media_filename ?: 'attachment') . '"',
                    ]);
                }
            }
        }

        abort(404, 'Media attachment unavailable.');
    }

    /**
     * Mark conversation as read.
     */
    public function markRead(WhatsAppConversation $conversation): JsonResponse
    {
        $conversation->markAsRead();
        return response()->json(['success' => true]);
    }

    /**
     * Assign conversation to a team agent or unassign.
     */
    public function assign(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'admin_id' => 'nullable|exists:admins,id',
        ]);

        $conversation->update([
            'assigned_to' => $validated['admin_id'] ?? null,
        ]);

        $agent = $conversation->fresh()->assignedAgent;

        return response()->json([
            'success' => true,
            'assigned_to' => $conversation->assigned_to,
            'agent_name' => $agent?->name ?? 'Unassigned',
            'message' => $agent ? "Assigned to {$agent->name}" : 'Conversation unassigned',
        ]);
    }

    /**
     * Change conversation lifecycle status (active/open, resolved, snoozed).
     */
    public function updateStatus(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:active,open,resolved,snoozed',
        ]);

        $status = $validated['status'] === 'open' ? 'active' : $validated['status'];
        $conversation->update(['status' => $status]);

        return response()->json([
            'success' => true,
            'status' => $conversation->status,
            'message' => 'Status updated to ' . ucfirst($conversation->status),
        ]);
    }

    /**
     * Add a private internal staff note to conversation.
     */
    public function addNote(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $admin = auth('admin')->user();

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'internal',
            'message_type' => 'note',
            'body' => $validated['note'],
            'sender_admin_id' => $admin?->id,
            'status' => 'delivered',
            'sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit('🔒 Note: ' . $validated['note'], 60),
        ]);

        return response()->json([
            'success' => true,
            'chat_message' => $message,
            'rendered_html' => view('admin.whatsapp.partials.message-bubble', ['msg' => $message])->render(),
        ]);
    }

    /**
     * Assemble customer summary context for the right-hand panel.
     */
    protected function buildCustomerContext(WhatsAppConversation $conversation): array
    {
        $user = $conversation->user;
        if (! $user) {
            $user = User::where('phone', $conversation->phone)
                ->orWhere('phone', substr($conversation->phone, 2))
                ->first();
        }

        $checkoutSession = $conversation->activeCheckoutSession;

        $orders = collect();
        $totalSpent = 0.0;
        $orderCount = 0;

        if ($user) {
            $orders = Order::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            $totalSpent = (float) Order::where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->sum('grand_total');

            $orderCount = Order::where('user_id', $user->id)->count();
        }

        return [
            'user' => $user,
            'orders' => $orders,
            'total_spent' => $totalSpent,
            'order_count' => $orderCount,
            'checkout_session' => $checkoutSession,
        ];
    }
}
