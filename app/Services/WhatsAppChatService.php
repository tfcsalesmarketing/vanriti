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

    /**
     * Find or create conversation by phone number.
     */
    public function getOrCreateConversation(string $phone, ?string $contactName = null, ?string $waId = null): WhatsAppConversation
    {
        return WhatsAppConversation::findOrCreateForPhone($phone, $contactName, $waId);
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

        $apiRes = $this->apiService->sendTextMessage($conversation->phone, $text);

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

        // Store file locally in public storage
        $storedPath = $file->store('whatsapp_media/' . date('Y/m'), 'public');
        $fullPath = Storage::disk('public')->path($storedPath);
        $publicUrl = asset('storage/' . $storedPath);

        // Upload to Meta Cloud API Media endpoint to get Media ID
        $uploadRes = $this->apiService->uploadMedia($fullPath, $mime);
        $mediaId = $uploadRes['media_id'] ?? null;

        // Fallback to sending public URL if media ID upload fails
        $mediaTarget = $mediaId ?: $publicUrl;

        $apiRes = $this->apiService->sendMediaMessage(
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
    public function recordInboundMessage(string $from, array $metaMsg, ?string $waId = null, ?string $senderName = null): ?WhatsAppMessage
    {
        $messageId = (string) ($metaMsg['id'] ?? '');
        $type = (string) ($metaMsg['type'] ?? 'text');

        // Check if message is already recorded
        if ($messageId !== '') {
            $existing = WhatsAppMessage::where('meta_message_id', $messageId)->first();
            if ($existing) {
                return $existing;
            }
        }

        $conversation = $this->getOrCreateConversation($from, $senderName, $waId);

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

        // Auto-respond via WhatsApp Chatbot if a matching rule exists
        if ($body) {
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

        // 1. Optional Header Component (Image)
        if (! empty($headerImageUrl)) {
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => ['link' => $headerImageUrl],
                    ],
                ],
            ];
        }

        // 2. Body Parameters
        if (! empty($parameters)) {
            $bodyParams = [];
            foreach ($parameters as $val) {
                $bodyParams[] = [
                    'type' => 'text',
                    'text' => (string) $val,
                ];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParams,
            ];
        }

        $apiRes = $this->apiService->sendTemplateMessage(
            $conversation->phone,
            $templateName,
            $langCode,
            $components
        );

        $status = ($apiRes['success'] ?? false) ? 'sent' : 'failed';
        $metaMsgId = $apiRes['message_id'] ?? null;
        $errorMsg = ($apiRes['success'] ?? false) ? null : ($apiRes['message'] ?? 'Template send failed');

        // Resolve authentic rendered template text
        $templateModel = \App\Models\WhatsAppTemplate::where('name', $templateName)->first();
        if ($templateModel && ! empty($templateModel->body_text)) {
            $renderedBody = $templateModel->body_text;
            foreach ($parameters as $idx => $val) {
                $renderedBody = str_replace(['{{' . ($idx + 1) . '}}', '{' . ($idx + 1) . '}'], (string) $val, $renderedBody);
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
                    $renderedBody = str_replace(['{{' . ($idx + 1) . '}}', '{' . ($idx + 1) . '}'], (string) $val, $renderedBody);
                }
            } else {
                $renderedBody = '📋 Template: ' . str_replace('_', ' ', $templateName);
                if (! empty($parameters)) {
                    $renderedBody .= ' (' . implode(', ', array_slice($parameters, 0, 3)) . ')';
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
}
