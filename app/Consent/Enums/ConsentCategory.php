<?php

namespace App\Consent\Enums;

/**
 * The closed set of consent categories VANRITI understands.
 *
 * necessary is always granted and is never presented as an optional choice;
 * the other three are optional and default to denied. Laravel owns the
 * authoritative category vocabulary: unknown categories are rejected by the
 * ConsentService rather than invented by callers.
 */
enum ConsentCategory: string
{
    case Necessary = 'necessary';

    case Analytics = 'analytics';

    case Advertising = 'advertising';

    case MarketingCommunications = 'marketing_communications';

    /**
     * The optional (user-toggable) categories. "necessary" is deliberately
     * excluded so it is never rendered as a toggleable choice.
     *
     * @return array<int, self>
     */
    public static function optional(): array
    {
        return [
            self::Analytics,
            self::Advertising,
            self::MarketingCommunications,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $category) => $category->value, self::cases());
    }
}
