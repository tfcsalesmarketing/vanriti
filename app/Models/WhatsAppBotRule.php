<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppBotRule extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_bot_rules';

    protected $fillable = [
        'name',
        'channel',
        'keywords',
        'match_type',
        'action_type',
        'response_text',
        'buttons',
        'is_active',
        'priority',
        'cooldown_minutes',
        'hit_count',
    ];

    protected function casts(): array
    {
        return [
            'buttons' => 'array',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'cooldown_minutes' => 'integer',
            'hit_count' => 'integer',
        ];
    }

    /**
     * Check if a given inbound text triggers this rule.
     */
    public function matches(string $incomingText): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $input = mb_strtolower(trim($incomingText));
        $keywords = array_filter(array_map('trim', explode(',', mb_strtolower($this->keywords))));

        foreach ($keywords as $kw) {
            if ($kw === '') {
                continue;
            }

            $matched = match ($this->match_type) {
                'exact' => ($input === $kw),
                'starts_with' => str_starts_with($input, $kw),
                default => str_contains($input, $kw), // contains
            };

            if ($matched) {
                return true;
            }
        }

        return false;
    }
}
