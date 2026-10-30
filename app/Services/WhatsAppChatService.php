<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppChatService
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService
    ) {}

    protected function apiForChannel(?string $channel): WhatsAppCloudApiService
    {
        return ($channel === 'tfc') ? $this->apiService->forChannel('tfc') : $this->apiService;
    }

    /**
     * Find or create conversation by phone number and channel.
     */
    public function getOrCreateConversation(
        string $phone,
        ?string $contactName = null,
        ?string $waId = null,
        string $channel = 'vanriti'
    ): WhatsAppConversation {
        return WhatsAppConversation::findOrCreateForPhone($phone, $contactName, $waId, $channel);
    }

    /**
     * Send plain text message from admin to customer.
     */
    public function sendTextMessage(WhatsAppConversation $conversation, string $text, mixed $sender = null): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['success' => false, 'message' => 'Message body cannot be empty.'];
        }

        $api = $this->apiForChannel($conversation->channel);
        $apiRes = $api->sendTextMessage($conversation->phone, $text);

        $status = ($apiRes['success'] ?? false) ? 'sent' : 'failed';
        $metaMsgId = $apiRes['message_id'] ?? null;
        $errorMsg = ($apiRes['success'] ?? false) ? null : ($apiRes['message'] ?? 'Send failed');

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'body' => $text,
            'meta_message_id' => $metaMsgId,
            'status' => $status,
            'error_message' => $errorMsg,
            'sender_admin_id' => $sender?->id,
            'sent_at' => ($status === 'sent') ? now() : null,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($text, 60),
        ]);

        return [
            'success' => $apiRes['success'] ?? false,
            'message' => $message,
            'error' => $errorMsg,
        ];
    }

    /**
     * Send uploaded media file (image, document, audio) from admin to customer.
     */
    public function sendMediaMessage(
        WhatsAppConversation $conversation,
        UploadedFile $file,
        ?string $caption = null,
        mixed $sender = null
    ): array {
        $mime = $file->getMimeType();
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        // Determine message type
        $type = 'document';
        if (str_starts_with($mime, 'image/')) {
            $type = 'image';
        } elseif (str_starts_with($mime, 'audio/')) {
            $type = 'audio';
        } elseif (str_starts_with($mime, 'video/')) {
            $type = 'video';
        }

        // Determine disk (S3 preferred; falls back to config)
        $disk = config('filesystems.whatsapp_disk', 's3');

        $api = $this->apiForChannel($conversation->channel);

        // 1. Upload to Meta Cloud API directly from the upload temporary stream (no permanent local file)
        $uploadRes = $api->uploadMedia($file->getRealPath(), $mime);
        $mediaId = $uploadRes['media_id'] ?? null;

        // 2. Store file on S3 bucket
        $storedPath = $file->store('whatsapp_media/' . date('Y/m'), $disk);
        $mediaUrl = Storage::disk($disk)->url($storedPath);

        // Fallback to sending S3 URL if media ID upload fails
        $mediaTarget = $mediaId ?: $mediaUrl;

        $apiRes = $api->sendMediaMessage(
            $conversation->phone,
            $type,
            $mediaTarget,
            $caption,
            $originalName
        );

        $status = ($apiRes['success'] ?? false) ? 'sent' : 'failed';
        $metaMsgId = $apiRes['message_id'] ?? null;
        $errorMsg = ($apiRes['success'] ?? false) ? null : ($apiRes['message'] ?? 'Media send failed');

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => $type,
            'body' => $caption,
            'media_id' => $mediaId,
            'media_url' => $storedPath,
            'media_mime_type' => $mime,
            'media_filename' => $originalName,
            'meta_message_id' => $metaMsgId,
            'status' => $status,
            'error_message' => $errorMsg,
            'sender_admin_id' => $sender?->id,
            'sent_at' => ($status === 'sent') ? now() : null,
        ]);

        $previewText = match ($type) {
            'image' => '📷 Photo' . ($caption ? ": {$caption}" : ''),
            'audio' => '🎵 Voice Note / Audio',
            'video' => '🎥 Video' . ($caption ? ": {$caption}" : ''),
            default => '📄 ' . $originalName,
        };

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($previewText, 60),
        ]);

        return [
            'success' => $apiRes['success'] ?? false,
            'message' => $message,
            'error' => $errorMsg,
        ];
    }

    /**
     * Process incoming webhook message and attach to conversation.
     */
    public function recordInboundMessage(
        string $from,
        array $metaMsg,
        ?string $waId = null,
        ?string $senderName = null,
        string $channel = 'vanriti'
    ): ?WhatsAppMessage {
        $messageId = (string) ($metaMsg['id'] ?? '');
        $type = (string) ($metaMsg['type'] ?? 'text');

        // Check if message is already recorded
        if ($messageId !== '') {
            $existing = WhatsAppMessage::where('meta_message_id', $messageId)->first();
            if ($existing) {
                return $existing;
            }
        }

        $conversation = $this->getOrCreateConversation($from, $senderName, $waId, $channel);

        $body = null;
        $mediaId = null;
        $mediaMime = null;
        $mediaFilename = null;
        $mediaUrl = null;
        $metadata = [];

        switch ($type) {
            case 'text':
                $body = $metaMsg['text']['body'] ?? '';
                break;

            case 'image':
                $mediaId = $metaMsg['image']['id'] ?? null;
                $mediaMime = $metaMsg['image']['mime_type'] ?? 'image/jpeg';
                $body = $metaMsg['image']['caption'] ?? null;
                break;

            case 'document':
                $mediaId = $metaMsg['document']['id'] ?? null;
                $mediaMime = $metaMsg['document']['mime_type'] ?? 'application/pdf';
                $mediaFilename = $metaMsg['document']['filename'] ?? 'document.pdf';
                $body = $metaMsg['document']['caption'] ?? null;
                break;

            case 'audio':
            case 'voice':
                $mediaId = $metaMsg['audio']['id'] ?? ($metaMsg['voice']['id'] ?? null);
                $mediaMime = $metaMsg['audio']['mime_type'] ?? 'audio/ogg';
                $type = 'audio';
                break;

            case 'video':
                $mediaId = $metaMsg['video']['id'] ?? null;
                $mediaMime = $metaMsg['video']['mime_type'] ?? 'video/mp4';
                $body = $metaMsg['video']['caption'] ?? null;
                break;

            case 'interactive':
                $interactive = $metaMsg['interactive'] ?? [];
                $btnTitle = $interactive['button_reply']['title']
                    ?? ($interactive['list_reply']['title'] ?? ($interactive['nfm_reply']['response_json'] ?? null));
                $body = $btnTitle ?: '[Interactive selection]';
                $metadata = $interactive;
                break;

            case 'order':
                $orderData = $metaMsg['order'] ?? ($metaMsg['interactive']['order'] ?? []);
                $itemCount = count($orderData['product_items'] ?? []);
                $body = "🛒 Sent WhatsApp Cart ({$itemCount} item" . ($itemCount !== 1 ? 's' : '') . ')';
                $metadata = $orderData;
                break;

            default:
                $body = "[Message: {$type}]";
                break;
        }

        // Create the message
        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'message_type' => $type,
            'body' => $body,
            'media_id' => $mediaId,
            'media_url' => $mediaUrl,
            'media_mime_type' => $mediaMime,
            'media_filename' => $mediaFilename,
            'meta_message_id' => $messageId ?: ('in_' . uniqid('', true)),
            'status' => 'delivered',
            'metadata' => ! empty($metadata) ? $metadata : null,
            'sent_at' => now(),
            'delivered_at' => now(),
        ]);

        // Refresh 24-hour service window and bump unread count
        $preview = match ($type) {
            'image' => '📷 ' . ($body ?: 'Photo'),
            'document' => '📄 ' . ($mediaFilename ?: 'Document'),
            'audio' => '🎵 Voice Note',
            'order' => $body ?: '🛒 Cart',
            default => $body ?: '[New message]',
        };

        $conversation->increment('unread_count');
        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($preview, 60),
            'window_expires_at' => now()->addHours(24),
        ]);

        // Auto-respond via WhatsApp Chatbot ONLY if explicitly enabled by admin in settings
        $botEnabled = ($conversation->channel === 'tfc')
            ? (bool) (function_exists('tfc_setting') ? tfc_setting('whatsapp_bot_auto_reply_enabled', false) : false)
            : (bool) setting('whatsapp_bot_auto_reply_enabled', false);

        if ($body && $botEnabled) {
            try {
                app(\App\Services\WhatsAppBotService::class)->handleInbound($conversation, $body);
            } catch (\Throwable $e) {
                Log::warning('WhatsApp chatbot auto-response failed', ['error' => $e->getMessage()]);
            }
        }

        return $message;
    }

    /**
     * Send approved Meta template message.
     */
    public function sendTemplateMessage(
        WhatsAppConversation $conversation,
        string $templateName,
        string $langCode = 'en',
        array $parameters = [],
        ?string $headerImageUrl = null,
        mixed $sender = null
    ): array {
        $components = [];

        // 0. Resolve template definition if registered
        $templateModel = \App\Models\WhatsAppTemplate::where('name', $templateName)->first();

        // 1. Header Component (Image)
        $isImageHeader = ($templateModel && strtoupper((string) $templateModel->header_type) === 'IMAGE')
            || (! $templateModel && ! empty($headerImageUrl));

        if ($isImageHeader) {
            $effectiveImageUrl = ! empty($headerImageUrl) ? $headerImageUrl : url('images/logo.png');
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => ['link' => $effectiveImageUrl],
                    ],
                ],
            ];
        }

        // 2. Body Parameters (supporting both named and positional variables)
        preg_match_all('/\{\{([a-zA-Z0-9_]+)\}\}/', (string) ($templateModel?->body_text ?? ''), $matches);
        $templateTokens = $matches[1] ?? [];

        if (! empty($parameters) && is_array($parameters)) {
            $bodyParams = [];
            $paramIndex = 0;

            foreach ($parameters as $key => $val) {
                $paramName = null;
                $paramText = '';

                if (is_array($val)) {
                    $paramName = $val['name'] ?? $val['parameter_name'] ?? null;
                    $paramText = trim((string) ($val['value'] ?? $val['text'] ?? ''));
                } elseif (! is_numeric($key)) {
                    $paramName = (string) $key;
                    $paramText = trim((string) $val);
                } else {
                    $paramText = trim((string) $val);
                    if (isset($templateTokens[$paramIndex]) && ! ctype_digit($templateTokens[$paramIndex])) {
                        $paramName = $templateTokens[$paramIndex];
                    }
                }

                if ($paramText === '') {
                    $paramText = '-';
                }

                $paramObj = [
                    'type' => 'text',
                    'text' => $paramText,
                ];
                if (! empty($paramName) && ! ctype_digit($paramName)) {
                    $paramObj['parameter_name'] = $paramName;
                }
                $bodyParams[] = $paramObj;
                $paramIndex++;
            }

            if (! empty($bodyParams)) {
                $components[] = [
                    'type' => 'body',
                    'parameters' => $bodyParams,
                ];
            }
        }

        // 3. Fallback language code from template if default
        if ($templateModel && ! empty($templateModel->language) && ($langCode === 'en' || empty($langCode))) {
            $langCode = $templateModel->language;
        }

        $api = $this->apiForChannel($conversation->channel);
        $apiRes = $api->sendTemplateMessage(
            $conversation->phone,
            $templateName,
            $langCode,
            $components
        );

        $status = ($apiRes['success'] ?? false) ? 'sent' : 'failed';
        $metaMsgId = $apiRes['message_id'] ?? null;
        $errorMsg = ($apiRes['success'] ?? false) ? null : ($apiRes['message'] ?? 'Template send failed');

        // Resolve authentic rendered template text
        if ($templateModel && ! empty($templateModel->body_text)) {
            $renderedBody = $templateModel->body_text;
            $idx = 0;
            foreach ($parameters as $key => $val) {
                $valText = is_array($val) ? ($val['value'] ?? $val['text'] ?? '') : (string) $val;
                $valName = is_array($val) ? ($val['name'] ?? null) : (! is_numeric($key) ? $key : ($templateTokens[$idx] ?? null));

                if (! empty($valName)) {
                    $renderedBody = str_replace(['{{' . $valName . '}}', '{' . $valName . '}'], (string) $valText, $renderedBody);
                }
                $renderedBody = str_replace(['{{' . ($idx + 1) . '}}', '{' . ($idx + 1) . '}'], (string) $valText, $renderedBody);
                $idx++;
            }
        } else {
            $presets = [
                'order_confirmation' => 'Hello {1}, thank you for your order! Your order #{2} is confirmed and will be delivered by {3}.',
                'abandoned_cart_recovery' => 'Hello {1}, you left items in your VANRITI cart! Complete your order now at {2}',
                'vanriti_welcome' => 'Hello {1}, welcome to VANRITI! Discover the pure essence of Ayurveda and natural holistic skincare with special offers on your first purchase.',
                'promotional_offer' => 'Hello {1}, celebrate with us! Enjoy {2} on your next order using coupon code: {3} at checkout.',
            ];
            if (isset($presets[$templateName])) {
                $renderedBody = $presets[$templateName];
                foreach ($parameters as $idx => $val) {
                    $valText = is_array($val) ? ($val['value'] ?? $val['text'] ?? '') : (string) $val;
                    $renderedBody = str_replace(['{{' . ($idx + 1) . '}}', '{' . ($idx + 1) . '}'], (string) $valText, $renderedBody);
                }
            } else {
                $renderedBody = '📋 Template: ' . str_replace('_', ' ', $templateName);
                if (! empty($parameters)) {
                    $cleanParams = array_map(fn($p) => is_array($p) ? ($p['value'] ?? '') : (string) $p, $parameters);
                    $renderedBody .= ' (' . implode(', ', array_slice($cleanParams, 0, 3)) . ')';
                }
            }
        }

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'template',
            'body' => $renderedBody,
            'media_url' => $headerImageUrl,
            'meta_message_id' => $metaMsgId,
            'status' => $status,
            'error_message' => $errorMsg,
            'metadata' => [
                'template_name' => $templateName,
                'lang_code' => $langCode,
                'parameters' => $parameters,
            ],
            'sender_admin_id' => $sender?->id,
            'sent_at' => ($status === 'sent') ? now() : null,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($renderedBody, 90),
        ]);

        return [
            'success' => $apiRes['success'] ?? false,
            'message' => $message,
            'error' => $errorMsg,
        ];
    }

    /**
     * Process status update from Meta webhook (sent, delivered, read, failed).
     */
    public function recordStatusUpdate(array $statusData): void
    {
        $metaMsgId = (string) ($statusData['id'] ?? '');
        $status = (string) ($statusData['status'] ?? '');

        if ($metaMsgId === '' || $status === '') {
            return;
        }

        $message = WhatsAppMessage::where('meta_message_id', $metaMsgId)->first();
        if ($message) {
            $updates = ['status' => $status];

            if ($status === 'sent' && empty($message->sent_at)) {
                $updates['sent_at'] = now();
            } elseif ($status === 'delivered') {
                $updates['delivered_at'] = now();
            } elseif ($status === 'read') {
                $updates['read_at'] = now();
                if (empty($message->delivered_at)) {
                    $updates['delivered_at'] = now();
                }
            } elseif ($status === 'failed') {
                $errors = $statusData['errors'] ?? [];
                $firstError = $errors[0]['title'] ?? ($errors[0]['message'] ?? 'Delivery failed');
                $updates['error_message'] = $firstError;
            }

            $message->update($updates);
        }

        // Check if message belongs to a campaign broadcast recipient
        $recipient = \App\Models\WhatsAppCampaignRecipient::where('meta_message_id', $metaMsgId)->first();
        if ($recipient) {
            $rUpdates = ['status' => $status];
            if ($status === 'sent') $rUpdates['sent_at'] = now();
            elseif ($status === 'delivered') $rUpdates['delivered_at'] = now();
            elseif ($status === 'read') $rUpdates['read_at'] = now();
            elseif ($status === 'failed') {
                $errors = $statusData['errors'] ?? [];
                $rUpdates['error_message'] = $errors[0]['title'] ?? 'Delivery failed';
            }
            $recipient->update($rUpdates);

            // Update aggregate counter on parent campaign
            $campaign = $recipient->campaign;
            if ($campaign) {
                if ($status === 'delivered') $campaign->increment('delivered_count');
                elseif ($status === 'read') $campaign->increment('read_count');
                elseif ($status === 'failed') $campaign->increment('failed_count');
            }
        }
    }

    /**
     * Completely purge a conversation, including all its messages, physical uploaded media files,
     * raw webhook logs, and checkout sessions from the database.
     */
    public function deleteConversationCompletely(WhatsAppConversation $conversation): array
    {
        $phone = $conversation->phone;
        $waId = $conversation->wa_id;
        $channel = $conversation->channel;

        // 1. Collect all messages and associated media files
        $messages = $conversation->messages()->get();
        $metaIds = $messages->pluck('meta_message_id')->filter()->unique()->toArray();
        $mediaUrls = $messages->pluck('media_url')->filter()->unique()->toArray();

        // 2. Delete physical media files from disk
        $disk = config('filesystems.whatsapp_disk', 's3');
        $filesDeleted = 0;
        foreach ($mediaUrls as $urlOrPath) {
            if (empty($urlOrPath)) continue;

            $relative = $urlOrPath;
            if (str_starts_with($urlOrPath, 'http')) {
                // Extract relative path if full URL
                $parsed = parse_url($urlOrPath, PHP_URL_PATH);
                $relative = ltrim((string) $parsed, '/');
                if (str_contains($relative, 'whatsapp_media/')) {
                    $relative = substr($relative, strpos($relative, 'whatsapp_media/'));
                }
            }

            try {
                if (Storage::disk($disk)->exists($relative)) {
                    Storage::disk($disk)->delete($relative);
                    $filesDeleted++;
                } elseif (Storage::disk('public')->exists($relative)) {
                    Storage::disk('public')->delete($relative);
                    $filesDeleted++;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed deleting WhatsApp media file during conversation purge', [
                    'path' => $relative,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. Delete all conversation messages
        $messagesDeleted = $conversation->messages()->delete();

        // 4. Delete webhook logs matching this phone or message IDs
        $phoneVariants = array_filter(array_unique([
            $phone,
            $waId,
            substr($phone, -10),
            '91' . substr($phone, -10),
            '+91' . substr($phone, -10),
        ]));

        $logsDeleted = 0;
        try {
            $logsQuery = \App\Models\WhatsAppWebhookLog::whereIn('from_phone', $phoneVariants);
            if (! empty($metaIds)) {
                $logsQuery->orWhereIn('message_id', $metaIds);
            }
            $logsDeleted = $logsQuery->delete();
        } catch (\Throwable $e) {
            Log::warning('Failed deleting WhatsApp webhook logs during conversation purge', ['error' => $e->getMessage()]);
        }

        // 5. If Vanriti channel: delete checkout sessions
        if ($channel === 'vanriti') {
            try {
                \App\Models\WhatsAppCheckoutSession::whereIn('phone', $phoneVariants)->delete();
            } catch (\Throwable) {}
        }

        // 6. Delete the conversation row itself
        $conversation->delete();

        Log::info('WhatsApp conversation purged successfully', [
            'phone' => $phone,
            'channel' => $channel,
            'messages_deleted' => $messagesDeleted,
            'files_deleted' => $filesDeleted,
            'logs_deleted' => $logsDeleted,
        ]);

        return [
            'success' => true,
            'phone' => $phone,
            'channel' => $channel,
            'messages_deleted' => $messagesDeleted,
            'files_deleted' => $filesDeleted,
            'logs_deleted' => $logsDeleted,
        ];
    }
}
