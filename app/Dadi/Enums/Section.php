<?php

namespace App\Dadi\Enums;

/**
 * The business sections a Dadi conversation may explore.
 *
 * Laravel owns the authoritative section of a conversation. This enum is the
 * closed set of sections the domain understands; unknown sections are rejected
 * by the ConversationState value object.
 */
enum Section: string
{
    case Hair = 'hair';
    case Skin = 'skin';
    case Wellness = 'wellness';

    public function label(): string
    {
        return match ($this) {
            self::Hair => 'Hair',
            self::Skin => 'Skin',
            self::Wellness => 'Wellness',
        };
    }
}
