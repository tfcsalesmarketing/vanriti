<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Exceptions\InvalidAiTurnException;
use App\Dadi\ValueObjects\AiRequest;
use PHPUnit\Framework\TestCase;

class AiRequestTest extends TestCase
{
    public function test_carries_plain_prompt_content(): void
    {
        $request = new AiRequest(
            systemInstructions: 'IDENTITY…BOUNDARIES',
            userMessage: 'CONTEXT…CUSTOMER MESSAGE',
            locale: 'hi',
        );

        self::assertSame('IDENTITY…BOUNDARIES', $request->systemInstructions);
        self::assertSame('CONTEXT…CUSTOMER MESSAGE', $request->userMessage);
        self::assertSame('hi', $request->locale);
        self::assertTrue($request->jsonMode);
    }

    public function test_defaults_to_english_locale_with_json_mode(): void
    {
        $request = new AiRequest(systemInstructions: 'S', userMessage: 'U');

        self::assertSame('en', $request->locale);
        self::assertTrue($request->jsonMode);
    }

    public function test_requires_system_instructions_and_user_message(): void
    {
        $this->expectException(InvalidAiTurnException::class);

        new AiRequest(systemInstructions: '   ', userMessage: 'U');
    }

    public function test_is_provider_agnostic(): void
    {
        $request = new AiRequest(systemInstructions: 'S', userMessage: 'U', jsonMode: false);

        self::assertSame(['systemInstructions', 'userMessage', 'locale', 'jsonMode'], array_keys(get_object_vars($request)));
        self::assertFalse($request->jsonMode);
    }
}
