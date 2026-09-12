<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Language\DadiLanguage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DadiLanguageTest extends TestCase
{
    public function test_supported_codes_are_constant_and_valid(): void
    {
        self::assertSame(['english', 'hindi', 'hinglish', 'other'], DadiLanguage::SUPPORTED);

        foreach (DadiLanguage::SUPPORTED as $code) {
            $language = DadiLanguage::from($code, $code === 'other' ? 'Marathi' : null);
            self::assertSame($code, $language->preferred);
        }
    }

    public function test_from_normalizes_case_and_whitespace(): void
    {
        self::assertSame('english', DadiLanguage::from(' English ')->preferred);
        self::assertSame('hinglish', DadiLanguage::from('HINGLISH')->preferred);
    }

    public function test_from_rejects_unknown_codes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DadiLanguage::from('french');
    }

    public function test_from_requires_a_name_for_other(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DadiLanguage::from('other');
    }

    public function test_other_accepts_a_trimmed_name_and_drops_names_for_known_languages(): void
    {
        $other = DadiLanguage::from('other', '  Marathi ');

        self::assertTrue($other->isOther());
        self::assertSame('Marathi', $other->name);

        self::assertNull(DadiLanguage::from('hindi', 'Hindi')->name);
    }

    public function test_type_predicates(): void
    {
        self::assertTrue(DadiLanguage::from('english')->isEnglish());
        self::assertTrue(DadiLanguage::from('hindi')->isHindi());
        self::assertTrue(DadiLanguage::from('hinglish')->isHinglish());
        self::assertTrue(DadiLanguage::from('other', 'Marathi')->isOther());
    }

    public function test_locale_hint_is_hi_only_for_actual_hindi(): void
    {
        self::assertSame('hi', DadiLanguage::from('hindi')->localeHint());
        self::assertSame('en', DadiLanguage::from('english')->localeHint());
        self::assertSame('en', DadiLanguage::from('hinglish')->localeHint());
        self::assertSame('en', DadiLanguage::from('other', 'Marathi')->localeHint());
    }

    public function test_display_names_are_customer_facing(): void
    {
        self::assertSame('English', DadiLanguage::from('english')->displayName());
        self::assertSame('हिन्दी', DadiLanguage::from('hindi')->displayName());
        self::assertSame('Hinglish', DadiLanguage::from('hinglish')->displayName());
        self::assertSame('Other', DadiLanguage::from('other', 'Marathi')->displayName());
    }

    public function test_welcome_titles_are_ui_only_and_language_aware(): void
    {
        self::assertSame(
            "Tell me, what's bothering you?",
            DadiLanguage::welcomeTitleFor('english'),
        );
        self::assertSame(
            'बताओ, क्या परेशानी हो रही है?',
            DadiLanguage::welcomeTitleFor('hindi'),
        );
        self::assertSame(
            'Batao, kya pareshaan kar raha hai?',
            DadiLanguage::welcomeTitleFor('hinglish'),
        );
        self::assertSame(
            "Baat karein · Let's talk",
            DadiLanguage::welcomeTitleFor('other'),
        );
    }

    public function test_welcome_title_is_null_for_unknown_codes(): void
    {
        self::assertNull(DadiLanguage::welcomeTitleFor('french'));
        self::assertNull(DadiLanguage::welcomeTitleFor(''));
    }
}
