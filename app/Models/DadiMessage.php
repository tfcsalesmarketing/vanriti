<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DadiMessage extends Model
{
    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    public const ROLE_SYSTEM = 'system';

    public const ROLE_TOOL = 'tool';

    protected $fillable = [
        'conversation_id',
        'role',
        'sequence',
        'content',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(DadiConversation::class, 'conversation_id');
    }

    /**
     * @return array<int,string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_ASSISTANT,
            self::ROLE_SYSTEM,
            self::ROLE_TOOL,
        ];
    }
}
