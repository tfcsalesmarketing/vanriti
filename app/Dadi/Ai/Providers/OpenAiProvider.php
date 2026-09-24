<?php

namespace App\Dadi\Ai\Providers;

use App\Dadi\Contracts\AiProvider;
use App\Dadi\Exceptions\AiProviderException;
use App\Dadi\ValueObjects\AiRequest;
use App\Dadi\ValueObjects\AiResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;

/**
 * OpenAI implementation of the AiProvider contract, via the Chat Completions
 * REST API. Uses Laravel's HTTP client exactly like the rest of the
 * application; no OpenAI SDK dependency.
 *
 * This class is the only place in Dadi that speaks OpenAI's wire format. It
 * maps every failure to a typed AiProviderException category so the engine can
 * stay provider-agnostic. Secrets are read from configuration, never logged
 * and never placed in the payload body.
 */
final class OpenAiProvider implements AiProvider
{
    public const ENDPOINT = '/chat/completions';

    private readonly Factory $http;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeout = 15,
        private readonly int $connectTimeout = 5,
        private readonly int $maxOutputTokens = 900,
        private readonly float $temperature = 0.8,
        private readonly bool $jsonMode = true,
        private readonly ?string $organization = null,
        ?Factory $http = null,
    ) {
        $this->http = $http ?? new Factory;
    }

    public function generate(AiRequest $request): AiResponse
    {
        if (trim($this->apiKey) === '') {
            throw new AiProviderException(
                AiProviderException::CATEGORY_CONFIGURATION,
                'OpenAI API key is not configured.',
            );
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $request->systemInstructions],
                ['role' => 'user', 'content' => $request->userMessage],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxOutputTokens,
        ];

        if ($request->jsonMode && $this->jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $start = hrtime(true);

        $response = $this->send($payload);

        $latencyMs = (hrtime(true) - $start) / 1e6;

        if ($response->failed()) {
            throw new AiProviderException(
                $this->failureCategory($response),
                $this->responseError($response),
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new AiProviderException(
                AiProviderException::CATEGORY_INVALID_RESPONSE,
                'OpenAI returned a non-JSON body.',
            );
        }

        $content = (string) ($json['choices'][0]['message']['content'] ?? '');
        $finishReason = $json['choices'][0]['finish_reason'] ?? null;
        $usage = is_array($json['usage'] ?? null) ? $json['usage'] : [];
        $requestId = $json['id'] ?? null;

        if (trim($content) === '') {
            throw new AiProviderException(
                AiProviderException::CATEGORY_INVALID_RESPONSE,
                'OpenAI returned empty content.',
            );
        }

        return new AiResponse(
            content: $content,
            data: $this->decode($content),
            provider: 'openai',
            model: $this->model,
            finishReason: is_string($finishReason) ? $finishReason : null,
            usage: $usage,
            latencyMs: round($latencyMs, 3),
            requestId: is_string($requestId) ? $requestId : null,
        );
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function send(array $payload): Response
    {
        try {
            return $this->http
                ->withToken($this->apiKey)
                ->acceptJson()
                ->withHeaders($this->headers())
                ->asJson()
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->post($this->endpoint(), $payload);
        } catch (ConnectionException $e) {
            throw new AiProviderException($this->connectionCategory($e), 'OpenAI connection failure.', previous: $e);
        } catch (\Throwable $e) {
            throw new AiProviderException(
                AiProviderException::CATEGORY_UNAVAILABLE,
                'OpenAI request failure ('.substr($e->getMessage(), 0, 200).').',
                previous: $e,
            );
        }
    }

    private function endpoint(): string
    {
        return rtrim($this->baseUrl, '/').self::ENDPOINT;
    }

    /**
     * @return array<string,string>
     */
    private function headers(): array
    {
        return $this->organization !== null && $this->organization !== ''
            ? ['OpenAI-Organization' => $this->organization]
            : [];
    }

    private function connectionCategory(ConnectionException $e): string
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'timed out') || str_contains($message, 'timeout')
            ? AiProviderException::CATEGORY_TIMEOUT
            : AiProviderException::CATEGORY_CONNECTION;
    }

    private function failureCategory(Response $response): string
    {
        $status = $response->status();

        return match (true) {
            $status === 401, $status === 403 => AiProviderException::CATEGORY_AUTHENTICATION,
            $status === 429 => AiProviderException::CATEGORY_RATE_LIMIT,
            $status >= 500 => AiProviderException::CATEGORY_UNAVAILABLE,
            default => AiProviderException::CATEGORY_INVALID_RESPONSE,
        };
    }

    /**
     * Build a sanitized failure message from the OpenAI error envelope. The API
     * key, request body and customer content never appear in it.
     */
    private function responseError(Response $response): string
    {
        $json = $response->json();
        $message = is_array($json) ? trim((string) ($json['error']['message'] ?? '')) : '';
        $type = is_array($json) ? trim((string) ($json['error']['type'] ?? '')) : '';

        return 'OpenAI error (HTTP '.$response->status()
            .($type !== '' ? ', type '.$type : '')
            .($message !== '' ? ', '.substr($message, 0, 200) : '')
            .')';
    }

    /**
     * Best-effort decode of the structured JSON body, tolerating wrap-around
     * fences. Returns null when the content is not a JSON object.
     *
     * @return array<string,mixed>|null
     */
    private function decode(string $content): ?array
    {
        $candidate = $content;

        if (str_starts_with($candidate, '```')) {
            $candidate = preg_replace('/^```(?:json)?\s*/i', '', $candidate) ?? $candidate;
            $candidate = preg_replace('/\s*```$/', '', $candidate) ?? $candidate;
        }

        $decoded = json_decode($candidate, true);

        return is_array($decoded) ? $decoded : null;
    }
}
