<?php

namespace App\Consent;

use App\Consent\Enums\ConsentCategory;
use App\Consent\Enums\ConsentSource;

/**
 * Immutable, centrally resolved consent snapshot.
 *
 * This is how the rest of the application is expected to ask about consent:
 *     $consent->allows('analytics')
 *     $consent->allows(ConsentCategory::Advertising)
 * Callers must never re-derive consent from raw cookies or table columns.
 * NOTE: this phase builds persistence + resolution; nothing consumes it for
 * tracking enforcement yet (that is M5.5).
 */
final readonly class ConsentState
{
    public function __construct(
        private bool $necessary,
        private bool $analytics,
        private bool $advertising,
        private bool $marketingCommunications,
        private string $consentVersion,
        private string $policyVersion,
        private ConsentSource $source,
        private int $timestamp,
        private bool $authenticated,
    ) {}

    /**
     * Safe default: necessary granted, all optional categories denied.
     */
    public static function defaults(bool $authenticated = false): self
    {
        return new self(
            necessary: true,
            analytics: false,
            advertising: false,
            marketingCommunications: false,
            consentVersion: (string) config('consent.version'),
            policyVersion: (string) config('consent.policy_version'),
            source: ConsentSource::System,
            timestamp: time(),
            authenticated: $authenticated,
        );
    }

    public function allows(ConsentCategory|string $category): bool
    {
        $category = $category instanceof ConsentCategory
            ? $category
            : ConsentCategory::tryFrom($category);

        if ($category === null) {
            return false;
        }

        return match ($category) {
            ConsentCategory::Necessary => $this->necessary,
            ConsentCategory::Analytics => $this->analytics,
            ConsentCategory::Advertising => $this->advertising,
            ConsentCategory::MarketingCommunications => $this->marketingCommunications,
        };
    }

    public function isGranted(ConsentCategory|string $category): bool
    {
        return $this->allows($category);
    }

    public function necessary(): bool
    {
        return $this->necessary;
    }

    public function analytics(): bool
    {
        return $this->analytics;
    }

    public function advertising(): bool
    {
        return $this->advertising;
    }

    public function marketingCommunications(): bool
    {
        return $this->marketingCommunications;
    }

    public function consentVersion(): string
    {
        return $this->consentVersion;
    }

    public function policyVersion(): string
    {
        return $this->policyVersion;
    }

    public function source(): ConsentSource
    {
        return $this->source;
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    /**
     * True when the recorded consent was made under a different consent
     * version than the one currently in force. Optional categories are then
     * held to denied until a fresh choice exists.
     */
    public function requiresReconsent(): bool
    {
        return $this->consentVersion !== (string) config('consent.version');
    }

    /**
     * @return array{necessary: bool, analytics: bool, advertising: bool, marketing_communications: bool, consent_version: string, policy_version: string, source: string, timestamp: int, authenticated: bool, requires_reconsent: bool}
     */
    public function toArray(): array
    {
        return [
            'necessary' => $this->necessary,
            'analytics' => $this->analytics,
            'advertising' => $this->advertising,
            'marketing_communications' => $this->marketingCommunications,
            'consent_version' => $this->consentVersion,
            'policy_version' => $this->policyVersion,
            'source' => $this->source->value,
            'timestamp' => $this->timestamp,
            'authenticated' => $this->authenticated,
            'requires_reconsent' => $this->requiresReconsent(),
        ];
    }
}
