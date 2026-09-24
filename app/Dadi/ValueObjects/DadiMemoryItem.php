<?php

namespace App\Dadi\ValueObjects;

use App\Dadi\Exceptions\InvalidConversationStateException;

/**
 * One classified, bounded entry in the conversational memory (Layer C).
 *
 * Memory items are contextual customer facts — what the customer said and how
 * Laravel understood it — never product truth. No product code, SKU, price or
 * stock value may ever live here; those belong to the Laravel catalogue only.
 *
 * Identity is (category, slot): re-stating the same fact replaces the value
 * instead of appending, so corrections overwrite stale information and the
 * most recent statement always wins. source_sequence keeps the first mention;
 * updated_sequence marks the latest statement that touched the slot.
 */
final readonly class DadiMemoryItem
{
    public const CATEGORIES = [
        'concern',
        'preference',
        'avoidance',
        'topic',
        'note',
    ];

    public const IMPORTANCES = [
        'low',
        'normal',
        'high',
    ];

    public function __construct(
        public string $category,
        public string $slot,
        public string $value,
        public bool $active = true,
        public string $importance = 'normal',
        public int $sourceSequence = 1,
        public int $updatedSequence = 1,
    ) {
        if (! in_array($category, self::CATEGORIES, true)) {
            throw new InvalidConversationStateException(
                "Unknown memory category [{$category}]. Allowed: ".implode(', ', self::CATEGORIES).'.',
            );
        }

        if (trim($slot) === '') {
            throw new InvalidConversationStateException('Memory slots must not be empty.');
        }

        if (trim($value) === '') {
            throw new InvalidConversationStateException('Memory values must not be empty.');
        }

        if (! in_array($importance, self::IMPORTANCES, true)) {
            throw new InvalidConversationStateException(
                "Unknown memory importance [{$importance}]. Allowed: low, normal, high.",
            );
        }

        if ($sourceSequence < 1 || $updatedSequence < $sourceSequence) {
            throw new InvalidConversationStateException('Memory sequences must be positive with updated >= source.');
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'slot' => $this->slot,
            'value' => $this->value,
            'active' => $this->active,
            'importance' => $this->importance,
            'source_sequence' => $this->sourceSequence,
            'updated_sequence' => $this->updatedSequence,
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            category: (string) ($data['category'] ?? ''),
            slot: (string) ($data['slot'] ?? ''),
            value: (string) ($data['value'] ?? ''),
            active: (bool) ($data['active'] ?? true),
            importance: (string) ($data['importance'] ?? 'normal'),
            sourceSequence: (int) ($data['source_sequence'] ?? $data['sourceSequence'] ?? 1),
            updatedSequence: (int) ($data['updated_sequence'] ?? $data['updatedSequence'] ?? 1),
        );
    }
}
