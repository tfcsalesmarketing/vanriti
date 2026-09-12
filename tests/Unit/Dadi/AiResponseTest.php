<?php

namespace Tests\Unit\Dadi;

use App\Dadi\ValueObjects\AiResponse;
use PHPUnit\Framework\TestCase;

class AiResponseTest extends TestCase
{
    public function test_carries_text_data_and_telemetry(): void
    {
        $response = new AiResponse(
            content: '{"reply":"Arre beta"}',
            data: ['reply' => 'Arre beta'],
            provider: 'openai',
            model: 'gpt-4o-mini',
            finishReason: 'stop',
            usage: ['prompt_tokens' => 12, 'completion_tokens' => 7, 'total_tokens' => 19],
            latencyMs: 431.2,
            requestId: 'chatcmpl-123',
        );

        self::assertSame('Arre beta', $response->data['reply']);
        self::assertSame('openai', $response->provider);
        self::assertSame('stop', $response->finishReason);
        self::assertSame(12, $response->promptTokens());
        self::assertSame(19, $response->totalTokens());
        self::assertSame(431.2, $response->latencyMs);
        self::assertSame('chatcmpl-123', $response->requestId);
    }

    public function test_data_is_null_when_not_structured(): void
    {
        $response = new AiResponse(content: 'just words', data: null);

        self::assertNull($response->data);
        self::assertSame('openai', $response->provider);
        self::assertSame('', $response->model);
        self::assertSame(0, $response->totalTokens());
    }

    public function test_never_carries_product_selection_fields(): void
    {
        $forbidden = ['sku', 'product_id', 'recommended_sku', 'price', 'stock'];

        $response = new AiResponse(content: 'x', data: ['reply' => 'x']);

        self::assertEmpty(array_intersect(array_keys($response->data ?? []), $forbidden));
        self::assertSame(
            ['content', 'data', 'provider', 'model', 'finishReason', 'usage', 'latencyMs', 'requestId'],
            array_keys(get_object_vars($response)),
        );
    }
}
