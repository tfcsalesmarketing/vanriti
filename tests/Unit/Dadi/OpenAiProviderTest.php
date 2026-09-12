<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Ai\Providers\OpenAiProvider;
use App\Dadi\Exceptions\AiProviderException;
use App\Dadi\ValueObjects\AiRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use PHPUnit\Framework\TestCase;

class OpenAiProviderTest extends TestCase
{
    private const KEY = 'sk-test-secret';

    private Factory $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->http = new Factory;
    }

    private function provider(array $overrides = []): OpenAiProvider
    {
        return new OpenAiProvider(
            baseUrl: $overrides['base_url'] ?? 'https://api.openai.com/v1',
            apiKey: $overrides['key'] ?? self::KEY,
            model: $overrides['model'] ?? 'gpt-4o-mini',
            timeout: $overrides['timeout'] ?? 15,
            connectTimeout: $overrides['connect_timeout'] ?? 5,
            maxOutputTokens: $overrides['max_output_tokens'] ?? 900,
            temperature: $overrides['temperature'] ?? 0.8,
            jsonMode: $overrides['json_mode'] ?? true,
            http: $this->http,
        );
    }

    private function request(): AiRequest
    {
        return new AiRequest(
            systemInstructions: 'IDENTITY…BOUNDARIES…SCHEMA',
            userMessage: 'CONTEXT…CUSTOMER MESSAGE',
            locale: 'hi',
        );
    }

    private function jsonBody(string $content): string
    {
        return json_encode([
            'id' => 'chatcmpl-abc123',
            'choices' => [[
                'message' => ['role' => 'assistant', 'content' => $content],
                'finish_reason' => 'stop',
            ]],
            'usage' => ['prompt_tokens' => 24, 'completion_tokens' => 11, 'total_tokens' => 35],
        ], JSON_THROW_ON_ERROR);
    }

    public function test_maps_a_successful_response_into_an_ai_response(): void
    {
        $this->http->fake([
            '*' => Factory::response($this->jsonBody('{"reply":"Arre beta","intent":"concern","understanding":{"section":"hair"}}'), 200),
        ]);

        $result = $this->provider()->generate($this->request());

        self::assertSame('Arre beta', $result->data['reply']);
        self::assertSame('hair', $result->data['understanding']['section']);
        self::assertSame('openai', $result->provider);
        self::assertSame('gpt-4o-mini', $result->model);
        self::assertSame('stop', $result->finishReason);
        self::assertSame(35, $result->totalTokens());
        self::assertNotNull($result->latencyMs);
        self::assertSame('chatcmpl-abc123', $result->requestId);
    }

    public function test_sends_the_expected_wire_format(): void
    {
        $this->http->fake([
            '*' => Factory::response($this->jsonBody('{"reply":"ok"}'), 200),
        ]);

        $this->provider()->generate($this->request());

        $sent = $this->http->recorded()[0][0];

        self::assertStringContainsString('/chat/completions', $sent->url());
        self::assertStringContainsString(self::KEY, $sent->headers()['Authorization'][0] ?? $sent->headers()['Authorization'] ?? '');

        $data = $sent->data();

        self::assertSame('gpt-4o-mini', $data['model']);
        self::assertSame(['type' => 'json_object'], $data['response_format']);
        self::assertSame('system', $data['messages'][0]['role']);
        self::assertSame('IDENTITY…BOUNDARIES…SCHEMA', $data['messages'][0]['content']);
        self::assertSame('user', $data['messages'][1]['role']);
        self::assertSame(0.8, $data['temperature']);
    }

    public function test_sends_organization_header_when_configured(): void
    {
        $this->http->fake([
            '*' => Factory::response($this->jsonBody('{"reply":"ok"}'), 200),
        ]);

        $provider = new OpenAiProvider(
            baseUrl: 'https://api.openai.com/v1',
            apiKey: self::KEY,
            model: 'gpt-4o-mini',
            organization: 'org-42',
            http: $this->http,
        );

        $provider->generate($this->request());

        $sent = $this->http->recorded()[0][0];
        $headers = $sent->headers();

        self::assertArrayHasKey('OpenAI-Organization', $headers);
        self::assertSame('org-42', $headers['OpenAI-Organization'][0] ?? $headers['OpenAI-Organization']);
    }

    public function test_missing_api_key_is_a_configuration_failure(): void
    {
        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('not configured');

        $this->provider(['key' => ''])->generate($this->request());
    }

    public function test_authentication_failure_is_categorized(): void
    {
        $this->http->fake([
            '*' => Factory::response(['error' => ['message' => 'Invalid API key', 'type' => 'invalid_request_error']], 401),
        ]);

        try {
            $this->provider()->generate($this->request());
            self::fail('Expected AiProviderException.');
        } catch (AiProviderException $e) {
            self::assertSame(AiProviderException::CATEGORY_AUTHENTICATION, $e->category);
        }
    }

    public function test_rate_limit_is_categorized(): void
    {
        $this->http->fake([
            '*' => Factory::response(['error' => ['message' => 'Rate limit reached', 'type' => 'rate_limit']], 429),
        ]);

        try {
            $this->provider()->generate($this->request());
            self::fail('Expected AiProviderException.');
        } catch (AiProviderException $e) {
            self::assertSame(AiProviderException::CATEGORY_RATE_LIMIT, $e->category);
        }
    }

    public function test_server_error_is_categorized_as_unavailable(): void
    {
        $this->http->fake([
            '*' => Factory::response([], 500),
        ]);

        try {
            $this->provider()->generate($this->request());
            self::fail('Expected AiProviderException.');
        } catch (AiProviderException $e) {
            self::assertSame(AiProviderException::CATEGORY_UNAVAILABLE, $e->category);
        }
    }

    public function test_timeout_is_categorized_as_a_connection_failure(): void
    {
        $this->http->fake(function (): never {
            throw new ConnectionException('curl error 28: Operation timed out');
        });

        try {
            $this->provider(['timeout' => 1])->generate($this->request());
            self::fail('Expected AiProviderException.');
        } catch (AiProviderException $e) {
            self::assertSame(AiProviderException::CATEGORY_TIMEOUT, $e->category);
        }
    }

    public function test_empty_content_is_an_invalid_response(): void
    {
        $this->http->fake([
            '*' => Factory::response($this->jsonBody(''), 200),
        ]);

        try {
            $this->provider()->generate($this->request());
            self::fail('Expected AiProviderException.');
        } catch (AiProviderException $e) {
            self::assertSame(AiProviderException::CATEGORY_INVALID_RESPONSE, $e->category);
        }
    }

    public function test_non_json_object_body_is_an_invalid_response(): void
    {
        $this->http->fake([
            '*' => Factory::response('42', 200),
        ]);

        try {
            $this->provider()->generate($this->request());
            self::fail('Expected AiProviderException.');
        } catch (AiProviderException $e) {
            self::assertSame(AiProviderException::CATEGORY_INVALID_RESPONSE, $e->category);
        }
    }

    public function test_fenced_json_content_is_decoded(): void
    {
        $this->http->fake([
            '*' => Factory::response($this->jsonBody('```json
{"reply":"Arre beta"}
```'), 200),
        ]);

        $result = $this->provider()->generate($this->request());

        self::assertSame('Arre beta', $result->data['reply']);
    }
}
