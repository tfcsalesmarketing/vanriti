<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppWebhookLog;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $messages = WhatsAppMessage::where('body', 'like', '%[Message:%')->get();

        foreach ($messages as $msg) {
            $newBody = null;
            $newType = $msg->message_type;

            // 1. Try to find corresponding webhook log
            $log = null;
            if (! empty($msg->meta_message_id)) {
                $log = WhatsAppWebhookLog::where('message_id', $msg->meta_message_id)->first();
            }

            if ($log) {
                $payload = is_array($log->payload) ? $log->payload : json_decode((string) $log->payload, true);
                $type = $payload['type'] ?? $log->event_type;

                if ($type === 'button') {
                    $btnText = (string) ($payload['button']['text'] ?? '');
                    $btnPayload = (string) ($payload['button']['payload'] ?? '');
                    $newBody = $btnText !== '' ? $btnText : ($btnPayload !== '' ? $btnPayload : '🔘 Button Reply');
                    $newType = 'button';
                } elseif ($type === 'interactive') {
                    $interactive = $payload['interactive'] ?? [];
                    $newBody = $interactive['button_reply']['title']
                        ?? ($interactive['list_reply']['title']
                        ?? ($interactive['button_reply']['id'] ?? '🔘 Selected Option'));
                    $newType = 'interactive';
                } elseif ($type === 'unsupported') {
                    $newBody = '⚠️ [Unsupported message format (e.g. Poll, Live Location, or Payment)]';
                    $newType = 'unsupported';
                } elseif ($type === 'location') {
                    $loc = $payload['location'] ?? [];
                    $locName = $loc['name'] ?? ($loc['address'] ?? null);
                    $newBody = $locName ? "📍 Location: {$locName}" : '📍 Shared Location';
                    $newType = 'location';
                } elseif ($type === 'reaction') {
                    $newBody = 'Reacted ' . ($payload['reaction']['emoji'] ?? '👍');
                    $newType = 'reaction';
                }
            }

            // 2. Fallbacks if log payload not found
            if (! $newBody) {
                if ($msg->body === '[Message: button]') {
                    $newBody = '🔘 Button Reply';
                    $newType = 'button';
                } elseif ($msg->body === '[Message: unsupported]') {
                    $newBody = '⚠️ [Unsupported message format (e.g. Poll, Live Location, or Payment)]';
                    $newType = 'unsupported';
                } else {
                    $newBody = preg_replace('/\[Message:\s*(\w+)\]/i', '[$1 message]', (string) $msg->body);
                }
            }

            $msg->update([
                'body' => $newBody,
                'message_type' => $newType,
            ]);

            // Update conversation last message preview if needed
            $convo = $msg->conversation;
            if ($convo && str_contains((string) $convo->last_message_preview, '[Message:')) {
                $convo->update([
                    'last_message_preview' => Str::limit($newBody, 60),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No-op (irreversible data cleanup)
    }
};
