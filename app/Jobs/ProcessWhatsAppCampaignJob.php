<?php

namespace App\Jobs;

use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public WhatsAppCampaign $campaign
    ) {}

    public function handle(WhatsAppCloudApiService $apiService): void
    {
        $campaign = $this->campaign;

        if ($campaign->status === 'completed' || $campaign->status === 'processing') {
            return;
        }

        $campaign->update(['status' => 'processing']);

        $pendingRecipients = $campaign->recipients()->where('status', 'pending')->get();

        foreach ($pendingRecipients as $recipient) {
            try {
                // Build components for Meta API
                $components = [];

                if (! empty($campaign->header_image_url)) {
                    $components[] = [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'image',
                                'image' => ['link' => $campaign->header_image_url],
                            ],
                        ],
                    ];
                }

                $parameters = (array) ($campaign->parameters ?? []);
                // Replace any {name} placeholder with recipient's actual name
                $formattedParams = [];
                foreach ($parameters as $param) {
                    $val = str_replace(['{name}', '{{name}}'], $recipient->customer_name ?: 'Customer', (string) $param);
                    $formattedParams[] = ['type' => 'text', 'text' => $val];
                }

                if (! empty($formattedParams)) {
                    $components[] = [
                        'type' => 'body',
                        'parameters' => $formattedParams,
                    ];
                }

                $res = $apiService->sendTemplateMessage(
                    $recipient->phone,
                    $campaign->template_name,
                    $campaign->template_lang ?: 'en',
                    $components
                );

                if ($res['success'] ?? false) {
                    $recipient->update([
                        'status' => 'sent',
                        'meta_message_id' => $res['message_id'] ?? null,
                        'sent_at' => now(),
                    ]);
                    $campaign->increment('sent_count');

                    // Record in conversation stream so it shows in chat with customer
                    try {
                        $cleanPhone = ltrim($recipient->phone, '+');
                        $conversation = \App\Models\WhatsAppConversation::firstOrCreate(
                            ['phone' => $cleanPhone],
                            [
                                'contact_name' => $recipient->customer_name,
                                'user_id' => $recipient->user_id,
                                'status' => 'active',
                            ]
                        );

                        $renderedBody = $campaign->template?->body_text ?? "📋 Campaign: {$campaign->name}";
                        if ($campaign->template && ! empty($parameters)) {
                            foreach ($parameters as $idx => $param) {
                                $val = str_replace(['{name}', '{{name}}'], $recipient->customer_name ?: 'Customer', (string) $param);
                                $renderedBody = str_replace(['{{' . ($idx + 1) . '}}', '{' . ($idx + 1) . '}'], $val, $renderedBody);
                            }
                        }

                        $uniqueMetaId = ! empty($res['message_id']) ? ($res['message_id'] . '_' . $recipient->id) : null;

                        \App\Models\WhatsAppMessage::create([
                            'whatsapp_conversation_id' => $conversation->id,
                            'direction' => 'outbound',
                            'message_type' => 'template',
                            'body' => $renderedBody,
                            'media_url' => $campaign->header_image_url,
                            'meta_message_id' => $uniqueMetaId,
                            'status' => 'sent',
                            'metadata' => [
                                'campaign_id' => $campaign->id,
                                'campaign_name' => $campaign->name,
                                'template_name' => $campaign->template_name,
                                'parameters' => $parameters,
                            ],
                            'sent_at' => now(),
                        ]);

                        $conversation->update([
                            'last_message_at' => now(),
                            'last_message_preview' => \Illuminate\Support\Str::limit($renderedBody, 90),
                        ]);
                    } catch (\Throwable $ex) {
                        Log::warning('Failed to record campaign message in conversation stream', [
                            'recipient_id' => $recipient->id,
                            'error' => $ex->getMessage(),
                        ]);
                    }
                } else {
                    $recipient->update([
                        'status' => 'failed',
                        'error_message' => $res['message'] ?? 'Send failed',
                    ]);
                    $campaign->increment('failed_count');
                }

                // Rate limiting pause between messages (Meta Cloud API compliance)
                usleep(50000); // 50ms pause (~20 messages/sec max)

            } catch (\Throwable $e) {
                Log::warning('Campaign message send exception', [
                    'campaign_id' => $campaign->id,
                    'phone' => $recipient->phone,
                    'error' => $e->getMessage(),
                ]);

                $recipient->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $campaign->increment('failed_count');
            }
        }

        $campaign->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
