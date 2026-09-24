<?php

namespace App\Dadi\Persistence\Casts;

use App\Dadi\ValueObjects\ConversationState;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Translates the Laravel-owned conversation understanding between the domain
 * (ConversationState value object) and its JSON persistence shape.
 */
class ConversationStateCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ConversationState
    {
        if (is_array($value)) {
            return ConversationState::fromArray($value);
        }

        if (! is_string($value) || $value === '') {
            return new ConversationState;
        }

        return ConversationState::fromArray(json_decode($value, true) ?? []);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $state = $value instanceof ConversationState
            ? $value
            : ConversationState::fromArray($value ?? []);

        return [$key => json_encode($state->toArray())];
    }
}
