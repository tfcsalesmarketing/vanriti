<?php

namespace App\Dadi\Enums;

/**
 * Laravel-owned safety verdict for a conversational turn.
 *
 *  - Clear:    no safety action required.
 *  - Advisory: the reply may continue conversationally, but must carry a
 *              caution. Product recommendations are restricted.
 *  - Block:    product recommendations are blocked entirely and the turn is
 *              routed to a safe, non-commercial response.
 */
enum SafetyVerdict: string
{
    case Clear = 'clear';
    case Advisory = 'advisory';
    case Block = 'block';

    public function blocksRecommendation(): bool
    {
        return $this === self::Block;
    }

    /**
     * Only a clean baseline permits product recommendations. An advisory
     * assessment restricts them; a block prohibits them formally.
     */
    public function permitsRecommendation(): bool
    {
        return $this === self::Clear;
    }
}
