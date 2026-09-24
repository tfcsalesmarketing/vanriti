<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Recommendation\ApprovedTextMatcher;
use PHPUnit\Framework\TestCase;

class ApprovedTextMatcherTest extends TestCase
{
    /**
     * @dataProvider assertionProvider
     */
    public function test_asserts_is_word_bounded_case_insensitive_and_negation_aware(
        string $text,
        string $trait,
        bool $expected,
    ): void {
        $matcher = new ApprovedTextMatcher;

        self::assertSame($expected, $matcher->asserts($text, $trait));
    }

    /**
     * @return array<string,array{string,string,bool}>
     */
    public static function assertionProvider(): array
    {
        return [
            'plain assertion' => ['formulated with fragrance', 'fragrance', true],
            'case insensitive' => ['Formulated With FRAGRANCE', 'fragrance', true],
            'word boundary' => ['heavyweight cream for long hair', 'heavy', false],
            'enclosed word' => ['A non-greasy, lightweight feel', 'lightweight', true],
            'trait preceding negation word' => ['fragrance free formula', 'fragrance', false],
            'free from' => ['free from fragrance', 'fragrance', false],
            'free of' => ['free of artificial fragrance', 'fragrance', false],
            'without lead' => ['without fragrance', 'fragrance', false],
            'no lead' => ['no fragrance added', 'fragrance', false],
            'not lead' => ['not a sticky finish', 'sticky', false],
            'never lead' => ['never sticky on long wear', 'sticky', false],
            'non lead' => ['non sticky absorption', 'sticky', false],
            'hyphenated negation' => ['non-sticky absorption', 'sticky', false],
            'hyphenated trait' => ['fragrance-free formula', 'fragrance free', true],
            'multi token trait' => ['a fragrance free formula', 'fragrance free', true],
            'unrelated speculation absent' => ['rich conditioning cream', 'sticky', false],
            'no text' => ['', 'lightweight', false],
            'no trait' => ['lightweight feel', '', false],
            'contraction doesn lead' => ["doesn't feel greasy", 'greasy', false],
            'contraction doesn bridge' => ["doesn't feel sticky on dry skin", 'sticky', false],
            'contraction don lead' => ["Don't use heavy oils", 'heavy', false],
            'contraction did not lead' => ["didn't get oily here", 'oily', false],
            'sensory verb without negation asserts' => ['feels greasy to the touch', 'greasy', true],
            'postposed nahi' => ['paraben nahi, plain base', 'paraben', false],
            'postposed nahi chahiye' => ['fragrance nahi chahiye', 'fragrance', false],
            'postposed se problem nahi' => ['fragrance se problem nahi hai', 'fragrance', false],
            'postposed bilkul nahi' => ['greasy bilkul nahi hota', 'greasy', false],
            'postposed chahiye is positive' => ['fragrance chahiye', 'fragrance', true],
            'nahi does not leak to earlier clause' => ['fragrance and paraben nahi', 'fragrance', true],
            'contraction negates only its own trait' => ['fragrance nahi, lightweight feel', 'lightweight', true],
        ];
    }

    public function test_hyphenated_phrases_match_their_spaced_trait(): void
    {
        $matcher = new ApprovedTextMatcher;

        self::assertTrue($matcher->asserts('a light-weight finish', 'light weight'));
        self::assertFalse($matcher->asserts('non-sticky absorption', 'sticky'));
    }
}
