<?php

namespace Tests\Unit;

use App\Consent\ConsentState;
use App\Consent\Enums\ConsentCategory;
use App\Consent\Enums\ConsentSource;
use Tests\TestCase;

class ConsentStateTest extends TestCase
{
    protected function tearDown(): void
    {
        config(['consent.version' => '2026-09-01']);

        parent::tearDown();
    }

    public function test_defaults_grant_necessary_and_deny_all_optional(): void
    {
        $state = ConsentState::defaults();

        $this->assertTrue($state->necessary());
        $this->assertFalse($state->analytics());
        $this->assertFalse($state->advertising());
        $this->assertFalse($state->marketingCommunications());
        $this->assertSame(config('consent.version'), $state->consentVersion());
        $this->assertSame(config('consent.policy_version'), $state->policyVersion());
        $this->assertSame(ConsentSource::System, $state->source());
        $this->assertFalse($state->isAuthenticated());
        $this->assertFalse($state->requiresReconsent());
    }

    public function test_allows_accepts_enum_and_string_forms(): void
    {
        $state = new ConsentState(
            necessary: true,
            analytics: true,
            advertising: false,
            marketingCommunications: true,
            consentVersion: config('consent.version'),
            policyVersion: config('consent.policy_version'),
            source: ConsentSource::Banner,
            timestamp: time(),
            authenticated: false,
        );

        $this->assertTrue($state->allows(ConsentCategory::Analytics));
        $this->assertTrue($state->allows('analytics'));
        $this->assertFalse($state->allows(ConsentCategory::Advertising));
        $this->assertFalse($state->allows('advertising'));
        $this->assertTrue($state->allows(ConsentCategory::Necessary));
        $this->assertTrue($state->allows('necessary'));
        $this->assertTrue($state->isGranted('marketing_communications'));
    }

    public function test_allows_rejects_unknown_categories(): void
    {
        $state = ConsentState::defaults();

        $this->assertFalse($state->allows('invented_category'));
        $this->assertFalse($state->allows(''));
        $this->assertFalse($state->allows('NECESSARY'));
    }

    public function test_allows_necessary_always_granted(): void
    {
        $state = new ConsentState(
            necessary: true,
            analytics: false,
            advertising: false,
            marketingCommunications: false,
            consentVersion: config('consent.version'),
            policyVersion: config('consent.policy_version'),
            source: ConsentSource::System,
            timestamp: time(),
            authenticated: true,
        );

        foreach (ConsentCategory::cases() as $category) {
            if ($category === ConsentCategory::Necessary) {
                $this->assertTrue($state->allows($category));
            }
        }
    }

    public function test_to_array_exposes_full_snapshot(): void
    {
        $state = new ConsentState(
            necessary: true,
            analytics: true,
            advertising: false,
            marketingCommunications: false,
            consentVersion: config('consent.version'),
            policyVersion: config('consent.policy_version'),
            source: ConsentSource::Api,
            timestamp: 1234567890,
            authenticated: true,
        );

        $this->assertSame([
            'necessary' => true,
            'analytics' => true,
            'advertising' => false,
            'marketing_communications' => false,
            'consent_version' => config('consent.version'),
            'policy_version' => config('consent.policy_version'),
            'source' => ConsentSource::Api->value,
            'timestamp' => 1234567890,
            'authenticated' => true,
            'requires_reconsent' => false,
        ], $state->toArray());
    }

    public function test_requires_reconsent_when_version_differs_from_current(): void
    {
        config(['consent.version' => '2026-09-01']);

        $fresh = new ConsentState(
            necessary: true,
            analytics: false,
            advertising: false,
            marketingCommunications: false,
            consentVersion: '2026-09-01',
            policyVersion: config('consent.policy_version'),
            source: ConsentSource::System,
            timestamp: time(),
            authenticated: false,
        );

        $stale = new ConsentState(
            necessary: true,
            analytics: false,
            advertising: false,
            marketingCommunications: false,
            consentVersion: '2024-08-01',
            policyVersion: config('consent.policy_version'),
            source: ConsentSource::System,
            timestamp: time(),
            authenticated: false,
        );

        $this->assertFalse($fresh->requiresReconsent());
        $this->assertTrue($stale->requiresReconsent());

        config(['consent.version' => '2027-01-01']);
        $this->assertTrue($fresh->requiresReconsent());
    }
}
