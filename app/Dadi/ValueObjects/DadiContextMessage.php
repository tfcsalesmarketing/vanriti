<?php

namespace App\Dadi\ValueObjects;

/**
 * A plain, immutable snapshot of one message within a DadiContext.
 *
 * Never an Eloquent model: the context the AI layer receives must stay
 * portable plain data, so this carries only the role, content and sequence
 * that the orchestrator needs.
 */
final readonly class DadiContextMessage
{
    public function __construct(
        public string $role,
        public string $content,
        public int $sequence = 1,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
            'sequence' => $this->sequence,
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            role: (string) ($data['role'] ?? ''),
            content: (string) ($data['content'] ?? ''),
            sequence: (int) ($data['sequence'] ?? 1),
        );
    }
}
