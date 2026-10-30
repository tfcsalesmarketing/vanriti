<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Tfc\TfcInquiry;
use App\Models\Tfc\TfcGrievanceTicket;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppQuickReply;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppChatService;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TfcWhatsAppInboxController extends Controller
{
    public function __construct(
        protected WhatsAppChatService $chatService,
        protected WhatsAppCloudApiService $apiService
    ) {}

    protected function tfcApi(): WhatsAppCloudApiService
    {
        return $this->apiService->forChannel('tfc');
    }

    /**
     * Display the TFC WhatsApp Inbox workspace.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $filter = (string) $request->input('filter', 'tfc'); // tfc, unread, inquiry, grievance, all, mine

        $query = WhatsAppConversation::with(['latestMessage', 'user', 'tfcInquiry', 'tfcGrievance'])
            ->orderByRaw('unread_count DESC')
            ->orderBy('last_message_at', 'desc');

        if ($filter === 'tfc' || empty($filter)) {
            $query->where('channel', 'tfc');
        } elseif ($filter === 'unread') {
            $query->where('channel', 'tfc')->where('unread_count', '>', 0);
        } elseif ($filter === 'inquiry') {
            $query->whereNotNull('tfc_inquiry_id');
        } elseif ($filter === 'grievance') {
            $query->whereNotNull('tfc_grievance_id');
        } elseif ($filter === 'mine') {
            $query->where('assigned_to', auth('admin')->id());
        } elseif ($filter === 'resolved') {
            $query->where('status', 'resolved');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('last_message_preview', 'like', "%{$search}%");
            });
        }

        $conversations = $query->paginate(30)->withQueryString();

        // Selected conversation
        $selectedId = $request->input('conversation_id');
        $activeConversation = null;

        if ($request->filled('inquiry_id')) {
            $inquiry = TfcInquiry::find($request->input('inquiry_id'));
            if ($inquiry && $inquiry->phone) {
                $activeConversation = WhatsAppConversation::findOrCreateForPhone(
                    $inquiry->phone,
                    $inquiry->name,
                    null,
                    'tfc',
                    $inquiry->id
                );
                $selectedId = $activeConversation->id;
            }
        } elseif ($request->filled('grievance_id')) {
            $grievance = TfcGrievanceTicket::find($request->input('grievance_id'));
            if ($grievance && $grievance->phone) {
                $activeConversation = WhatsAppConversation::findOrCreateForPhone(
                    $grievance->phone,
                    $grievance->consumer_name,
                    null,
                    'tfc',
                    null,
                    $grievance->id
                );
                $selectedId = $activeConversation->id;
            }
        } elseif ($request->filled('phone')) {
            $activeConversation = WhatsAppConversation::findOrCreateForPhone(
                $request->input('phone'),
                $request->input('name'),
                null,
                'tfc'
            );
            $selectedId = $activeConversation->id;
        }

        if ($selectedId && ! $activeConversation) {
            $activeConversation = WhatsAppConversation::with(['user', 'assignedAgent', 'tfcInquiry', 'tfcGrievance'])->find($selectedId);
        }

        if ($activeConversation) {
            $activeConversation->markAsRead();
            $activeConversation->load([
                'messages' => function ($q) {
                    $q->orderBy('created_at', 'asc')->take(100);
                },
                'tfcInquiry',
                'tfcGrievance',
                'user',
                'assignedAgent',
            ]);
        }

        $admins = Admin::where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']);
        $quickReplies = WhatsAppQuickReply::where('is_active', true)
            ->where(function ($q) {
                $q->where('channel', 'tfc')->orWhere('channel', 'vanriti');
            })
            ->orderBy('category')
            ->orderBy('shortcut')
            ->get();
        $approvedTemplates = WhatsAppTemplate::where('channel', 'tfc')->where('status', 'APPROVED')->orderBy('name')->get();

        $unreadCount = WhatsAppConversation::where('channel', 'tfc')->where('unread_count', '>', 0)->count();

        return view('admin.tfc.whatsapp.inbox', compact(
            'conversations',
            'activeConversation',
            'admins',
            'quickReplies',
            'approvedTemplates',
            'search',
            'filter',
            'unreadCount'
        ));
    }

    /**
     * Poll messages for an active conversation (AJAX).
     */
    public function poll(WhatsAppConversation $conversation): JsonResponse
    {
        $afterId = request()->query('after_id', 0);

        $newMessages = $conversation->messages()
            ->where('id', '>', $afterId)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($newMessages->isNotEmpty()) {
            $conversation->markAsRead();
        }

        return response()->json([
            'success' => true,
            'messages' => $newMessages,
            'window_open' => $conversation->isWindowOpen(),
            'window_remaining' => $conversation->windowRemainingFormatted(),
            'unread_count' => $conversation->unread_count,
        ]);
    }

    /**
     * Poll conversation list updates (AJAX).
     */
    public function pollConversations(Request $request): JsonResponse
    {
        $conversations = WhatsAppConversation::with(['latestMessage', 'tfcInquiry'])
            ->where('channel', 'tfc')
            ->orderByRaw('unread_count DESC')
            ->orderBy('last_message_at', 'desc')
            ->take(30)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'phone' => $c->phone,
                    'contact_name' => $c->contact_name,
                    'unread_count' => $c->unread_count,
                    'last_message_preview' => Str::limit($c->last_message_preview, 45),
                    'last_message_time' => $c->last_message_at ? $c->last_message_at->diffForHumans() : '',
                    'status' => $c->status,
                    'is_inquiry' => ! empty($c->tfc_inquiry_id),
                    'is_grievance' => ! empty($c->tfc_grievance_id),
                ];
            });

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
            'total_unread' => WhatsAppConversation::where('channel', 'tfc')->where('unread_count', '>', 0)->count(),
        ]);
    }

    /**
     * Send plain text message.
     */
    public function sendText(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:4096'],
        ]);

        $body = trim((string) $request->input('message'));
        $api = $this->tfcApi();

        if (! $api->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp Cloud API is not configured. Please check TFC WhatsApp settings.',
            ], 422);
        }

        $res = $api->sendTextMessage($conversation->phone, $body);

        if (! ($res['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Failed to dispatch WhatsApp message via Meta Cloud API.',
            ], 400);
        }

        $msgId = $res['data']['messages'][0]['id'] ?? ('out_' . uniqid('', true));

        $msg = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'body' => $body,
            'meta_message_id' => $msgId,
            'status' => 'sent',
            'sender_admin_id' => auth('admin')->id(),
            'sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($body, 100),
            'channel' => 'tfc',
        ]);

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    }

    /**
     * Send media file (image, PDF document).
     */
    public function sendMedia(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:16384', 'mimes:jpeg,jpg,png,webp,pdf'],
            'caption' => ['nullable', 'string', 'max:1024'],
        ]);

        $file = $request->file('file');
        $caption = $request->input('caption');
        $api = $this->tfcApi();

        if (! $api->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp Cloud API is not configured.',
            ], 422);
        }

        $disk = config('filesystems.whatsapp_disk', 's3');
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $type = str_starts_with($mime, 'image/') ? 'image' : 'document';
        $storedPath = $file->store('whatsapp_media/tfc/' . date('Y/m'), $disk);
        $fileUrl = Storage::disk($disk)->url($storedPath);

        $res = $api->sendMediaMessage(
            $conversation->phone,
            $type,
            $fileUrl,
            $caption,
            $file->getClientOriginalName()
        );

        if (! ($res['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Failed to send media to Meta API.',
            ], 400);
        }

        $msgId = $res['data']['messages'][0]['id'] ?? ('out_media_' . uniqid('', true));

        $msg = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => $type,
            'body' => $caption,
            'media_url' => $fileUrl,
            'media_mime_type' => $mime,
            'media_filename' => $file->getClientOriginalName(),
            'meta_message_id' => $msgId,
            'status' => 'sent',
            'sender_admin_id' => auth('admin')->id(),
            'sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => ($type === 'image' ? '📷 Image' : '📄 Document') . ($caption ? ': ' . Str::limit($caption, 60) : ''),
            'channel' => 'tfc',
        ]);

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    }

    /**
     * Send approved WhatsApp template.
     */
    public function sendTemplate(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $request->validate([
            'template_name' => ['required', 'string', 'max:150'],
            'language' => ['nullable', 'string', 'max:10'],
            'parameters' => ['nullable', 'array'],
        ]);

        $templateName = $request->input('template_name');
        $language = $request->input('language', 'en');
        $params = $request->input('parameters', []);
        $api = $this->tfcApi();

        if (! $api->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Cloud API not configured.'], 422);
        }

        $components = [];
        if (! empty($params)) {
            $bodyParams = [];
            foreach ($params as $paramValue) {
                $bodyParams[] = ['type' => 'text', 'text' => (string) $paramValue];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParams,
            ];
        }

        $res = $api->sendTemplateMessage($conversation->phone, $templateName, $language, $components);

        if (! ($res['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Failed to send WhatsApp template via Meta.',
            ], 400);
        }

        $msgId = $res['data']['messages'][0]['id'] ?? ('tmpl_' . uniqid('', true));

        $msg = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'template',
            'body' => "Template: {$templateName}",
            'meta_message_id' => $msgId,
            'status' => 'sent',
            'sender_admin_id' => auth('admin')->id(),
            'sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => "📋 Template: {$templateName}",
            'channel' => 'tfc',
        ]);

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
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
     * Update conversation status (active, resolved, closed).
     */
    public function updateStatus(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:active,resolved,closed,spam'],
        ]);

        $conversation->update(['status' => $request->input('status')]);
        return response()->json(['success' => true, 'status' => $conversation->status]);
    }

    /**
     * Assign conversation to an agent.
     */
    public function assign(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $request->validate([
            'assigned_to' => ['nullable', 'exists:admins,id'],
        ]);

        $conversation->update(['assigned_to' => $request->input('assigned_to')]);
        return response()->json([
            'success' => true,
            'assigned_agent' => $conversation->assignedAgent?->name ?? 'Unassigned',
        ]);
    }

    /**
     * Save internal agent notes.
     */
    public function addNote(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $conversation->update(['notes' => $request->input('notes')]);
        return response()->json(['success' => true]);
    }

    /**
     * Stream media for preview.
     */
    public function streamMedia(WhatsAppMessage $message): Response
    {
        if (! $message->media_url) {
            abort(404, 'Media not found');
        }

        if (str_starts_with($message->media_url, 'http://') || str_starts_with($message->media_url, 'https://')) {
            return redirect()->away($message->media_url);
        }

        $disk = config('filesystems.whatsapp_disk', 's3');
        if (! Storage::disk($disk)->exists($message->media_url)) {
            abort(404, 'File not found on storage.');
        }

        return response(Storage::disk($disk)->get($message->media_url), 200, [
            'Content-Type' => $message->media_mime_type ?: 'application/octet-stream',
        ]);
    }
}
