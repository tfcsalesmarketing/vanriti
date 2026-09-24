<?php

namespace App\Consent;

use App\Consent\Enums\ConsentCategory;
use App\Consent\Enums\ConsentSource;
use App\Models\ConsentChoice;
use App\Models\ConsentPreference;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Single authoritative server-side consent resolver and mutator.
 *
 * Controllers, Blade, JavaScript, Meta and GA4/GTM integrations must consume a
 * resolved ConsentState (e.g. via allows()) rather than re-interpreting raw
 * cookies or database columns. This phase provides the storage/model/backend
 * foundation only: nothing enforces consent on GTM/GA4/Meta/Dadi yet.
 *
 * Resolution rules (safe defaults, fail closed for optional categories):
 *   - necessary is always granted and can never be declined.
 *   - analytics / advertising / marketing_communications default to denied.
 *   - missing, malformed, tampered or unsupported-version consent resolves to
 *     those defaults; consent failures never break the request.
 *   - authenticated state is authoritative over the anonymous cookie.
 */
class ConsentService
{
    protected ?ConsentState $resolved = null;

    /**
     * Resolve the current request's consent state (authenticated when the web
     * guard is logged in, otherwise anonymous from the encrypted cookie).
     */
    public function resolve(): ConsentState
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $user = auth('web')->user();

        return $user instanceof User
            ? $this->resolveFor($user)
            : $this->resolveAnonymous();
    }

    /**
     * Resolve consent for a specific authenticated user. The consent_preferences
     * row is authoritative; a missing row resolves to safe defaults without
     * writing anything.
     */
    public function resolveFor(User $user): ConsentState
    {
        try {
            $preference = ConsentPreference::query()
                ->where('user_id', $user->id)
                ->first();
        } catch (\Throwable $e) {
            Log::warning('Consent resolution unavailable; using safe defaults.', [
                'error' => $e->getMessage(),
            ]);

            return $this->resolved = ConsentState::defaults(authenticated: true);
        }

        return $this->resolved = $preference
            ? $this->stateFromPreference($preference)
            : ConsentState::defaults(authenticated: true);
    }

    /**
     * Resolve anonymous consent from the encrypted first-party cookie.
     * Missing/malformed/tampered/unsupported consent fails closed to defaults.
     */
    public function resolveAnonymous(): ConsentState
    {
        if (! (bool) config('consent.enabled', true)) {
            return $this->resolved = ConsentState::defaults();
        }

        $raw = request()->cookie((string) config('consent.cookie_name', 'vanriti_consent'));

        if (! is_string($raw) || $raw === '') {
            return $this->resolved = ConsentState::defaults();
        }

        $payload = $this->decodePayload($raw);

        if ($payload === null) {
            return $this->resolved = ConsentState::defaults();
        }

        return $this->resolved = $this->stateFromPayload($payload, authenticated: false);
    }

    /**
     * Convenience for current-scope checks: $consent->allows('analytics').
     */
    public function allows(ConsentCategory|string $category): bool
    {
        return $this->resolve()->allows($category);
    }

    /**
     * Update an authenticated user's current consent state and append history.
     *
     * @param  array<string, bool>  $states  Optional category states keyed by
     *                                       consent category value
     *                                       (analytics, advertising,
     *                                       marketing_communications).
     *                                       necessary is forced granted.
     */
    public function update(User $user, array $states, ConsentSource|string $source): ConsentState
    {
        $source = $this->normalizeSource($source);
        $optional = $this->extractStates($states);

        $preference = $this->persistPreferences(
            $user,
            $optional['analytics'],
            $optional['advertising'],
            $optional['marketing_communications'],
            $source,
        );

        $this->recordHistory(
            $preference,
            $optional['analytics'],
            $optional['advertising'],
            $optional['marketing_communications'],
            $source,
        );

        return $this->resolved = $this->stateFromPreference($preference);
    }

    /**
     * Record anonymous optional consent choices in the encrypted cookie.
     *
     * @param  array<string, bool>  $states  Same shape as update().
     */
    public function updateAnonymous(array $states, ConsentSource|string $source): ConsentState
    {
        $source = $this->normalizeSource($source);
        $optional = $this->extractStates($states);

        $payload = $this->buildPayload($optional, $source);
        $this->queueCookie($payload);

        return $this->resolved = $this->stateFromPayload($payload, authenticated: false);
    }

    /**
     * Withdraw optional consent for an authenticated user. With no categories
     * given, all optional categories are withdrawn. necessary is untouched.
     *
     * @param  array<int, ConsentCategory|string>  $categories
     */
    public function withdraw(User $user, array $categories = [], ConsentSource|string $source = ConsentSource::Settings): ConsentState
    {
        $source = $this->normalizeSource($source);
        $targets = $categories === []
            ? ConsentCategory::optional()
            : $this->normalizeOptionalCategories($categories);

        $preference = ConsentPreference::query()
            ->where('user_id', $user->id)
            ->first();

        $optional = [
            'analytics' => $preference ? (bool) $preference->analytics : false,
            'advertising' => $preference ? (bool) $preference->advertising : false,
            'marketing_communications' => $preference ? (bool) $preference->marketing_communications : false,
        ];

        foreach ($targets as $category) {
            $optional[$category->value] = false;
        }

        $preference = $this->persistPreferences(
            $user,
            $optional['analytics'],
            $optional['advertising'],
            $optional['marketing_communications'],
            $source,
        );

        $this->recordHistory(
            $preference,
            $optional['analytics'],
            $optional['advertising'],
            $optional['marketing_communications'],
            $source,
        );

        return $this->resolved = $this->stateFromPreference($preference);
    }

    /**
     * Withdraw optional consent for an anonymous visitor (encrypted cookie).
     */
    public function withdrawAnonymous(array $categories = [], ConsentSource|string $source = ConsentSource::Settings): ConsentState
    {
        $source = $this->normalizeSource($source);
        $targets = $categories === []
            ? ConsentCategory::optional()
            : $this->normalizeOptionalCategories($categories);

        $optional = [
            'analytics' => false,
            'advertising' => false,
            'marketing_communications' => false,
        ];

        $raw = request()->cookie((string) config('consent.cookie_name', 'vanriti_consent'));
        $payload = is_string($raw) ? $this->decodePayload($raw) : null;

        if ($payload !== null) {
            $optional = [
                'analytics' => (bool) $payload['analytics'],
                'advertising' => (bool) $payload['advertising'],
                'marketing_communications' => (bool) $payload['marketing_communications'],
            ];
        }

        foreach ($targets as $category) {
            $optional[$category->value] = false;
        }

        $payload = $this->buildPayload($optional, $source);
        $this->queueCookie($payload);

        return $this->resolved = $this->stateFromPayload($payload, authenticated: false);
    }

    /**
     * Anonymous → authenticated carry-forward (approved policy):
     *   CASE A (no existing prefs): carry the anonymous choices into the
     *     account, recorded with source carried_from_anonymous.
     *   CASE B (existing prefs): existing chose-out is kept; anonymous never
     *     overrides.
     *   CASE C: denied anonymous consent is never upgraded by authentication.
     * Idempotent and never throws; failures leave the account untouched.
     */
    public function mergeAnonymousIntoUser(User $user): bool
    {
        try {
            if (! (bool) config('consent.enabled', true)) {
                return false;
            }

            // CASE B + idempotency: an existing preference row always wins.
            if (ConsentPreference::query()->where('user_id', $user->id)->exists()) {
                return false;
            }

            $raw = request()->cookie((string) config('consent.cookie_name', 'vanriti_consent'));

            if (! is_string($raw) || $raw === '') {
                // No explicit anonymous choice was recorded; leave the account
                // untouched (it resolves to safe defaults until a choice is made).
                return false;
            }

            $payload = $this->decodePayload($raw);

            if ($payload === null) {
                return false; // malformed/tampered → safe defaults, nothing to carry.
            }

            // CASE C guard: never upgrade. Only carry the recorded anonymous
            // values (including denied ones) for the current version.
            if ($payload['version'] !== (string) config('consent.version')) {
                return false; // stale choice → re-consent required instead.
            }

            $preference = $this->persistPreferences(
                $user,
                (bool) $payload['analytics'],
                (bool) $payload['advertising'],
                (bool) $payload['marketing_communications'],
                ConsentSource::CarriedFromAnonymous,
            );

            $this->recordHistory(
                $preference,
                (bool) $payload['analytics'],
                (bool) $payload['advertising'],
                (bool) $payload['marketing_communications'],
                ConsentSource::CarriedFromAnonymous,
            );

            $this->resolved = $this->stateFromPreference($preference);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Consent carry-forward skipped; account consent untouched.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function persistPreferences(
        User $user,
        bool $analytics,
        bool $advertising,
        bool $marketingCommunications,
        ConsentSource $source,
    ): ConsentPreference {
        $preference = ConsentPreference::firstOrNew(['user_id' => $user->id]);

        $preference->necessary = true;
        $preference->analytics = $analytics;
        $preference->advertising = $advertising;
        $preference->marketing_communications = $marketingCommunications;
        $preference->consent_version = (string) config('consent.version');
        $preference->policy_version = (string) config('consent.policy_version');
        $preference->source = $source->value;
        $preference->consented_at = now();
        $preference->save();

        return $preference;
    }

    protected function recordHistory(
        ConsentPreference $preference,
        bool $analytics,
        bool $advertising,
        bool $marketingCommunications,
        ConsentSource $source,
    ): ConsentChoice {
        return ConsentChoice::create([
            'user_id' => $preference->user_id,
            'necessary' => true,
            'analytics' => $analytics,
            'advertising' => $advertising,
            'marketing_communications' => $marketingCommunications,
            'consent_version' => (string) $preference->consent_version,
            'policy_version' => (string) $preference->policy_version,
            'source' => $source->value,
        ]);
    }

    protected function stateFromPreference(ConsentPreference $preference): ConsentState
    {
        $stale = $preference->consent_version !== (string) config('consent.version');

        return new ConsentState(
            necessary: true,
            analytics: $stale ? false : (bool) $preference->analytics,
            advertising: $stale ? false : (bool) $preference->advertising,
            marketingCommunications: $stale ? false : (bool) $preference->marketing_communications,
            consentVersion: (string) $preference->consent_version,
            policyVersion: (string) $preference->policy_version,
            source: ConsentSource::tryFrom((string) $preference->source) ?? ConsentSource::System,
            timestamp: $preference->consented_at?->getTimestamp() ?? time(),
            authenticated: true,
        );
    }

    protected function stateFromPayload(array $payload, bool $authenticated): ConsentState
    {
        $stale = $payload['version'] !== (string) config('consent.version');

        return new ConsentState(
            necessary: true,
            analytics: $stale ? false : (bool) $payload['analytics'],
            advertising: $stale ? false : (bool) $payload['advertising'],
            marketingCommunications: $stale ? false : (bool) $payload['marketing_communications'],
            consentVersion: (string) $payload['version'],
            policyVersion: (string) $payload['policy_version'],
            source: ConsentSource::tryFrom((string) $payload['source']) ?? ConsentSource::System,
            timestamp: (int) $payload['timestamp'],
            authenticated: $authenticated,
        );
    }

    /**
     * @param  array{analytics: bool, advertising: bool, marketing_communications: bool}  $optional
     * @return array<string, mixed>
     */
    protected function buildPayload(array $optional, ConsentSource $source): array
    {
        return [
            'version' => (string) config('consent.version'),
            'policy_version' => (string) config('consent.policy_version'),
            'necessary' => true,
            'analytics' => $optional['analytics'],
            'advertising' => $optional['advertising'],
            'marketing_communications' => $optional['marketing_communications'],
            'source' => $source->value,
            'timestamp' => time(),
        ];
    }

    /**
     * @param  array<string, bool>  $states
     * @return array{analytics: bool, advertising: bool, marketing_communications: bool}
     */
    protected function extractStates(array $states): array
    {
        $optional = ConsentCategory::optional();

        $result = [];

        foreach ($optional as $category) {
            $result[$category->value] = (bool) ($states[$category->value] ?? false);
        }

        return [
            'analytics' => $result[ConsentCategory::Analytics->value],
            'advertising' => $result[ConsentCategory::Advertising->value],
            'marketing_communications' => $result[ConsentCategory::MarketingCommunications->value],
        ];
    }

    /**
     * @param  array<int, ConsentCategory|string>  $categories
     * @return array<int, ConsentCategory>
     */
    protected function normalizeOptionalCategories(array $categories): array
    {
        $resolved = [];

        foreach ($categories as $category) {
            $parsed = $category instanceof ConsentCategory
                ? $category
                : ConsentCategory::tryFrom((string) $category);

            if ($parsed === null) {
                throw new InvalidArgumentException("Unsupported consent category: {$category}");
            }

            if ($parsed === ConsentCategory::Necessary) {
                continue; // necessary cannot be declined.
            }

            $resolved[$parsed->value] = $parsed;
        }

        return array_values($resolved);
    }

    protected function normalizeSource(ConsentSource|string $source): ConsentSource
    {
        if ($source instanceof ConsentSource) {
            return $source;
        }

        $parsed = ConsentSource::tryFrom($source);

        if ($parsed === null) {
            throw new InvalidArgumentException("Unsupported consent source: {$source}");
        }

        return $parsed;
    }

    protected function decodePayload(string $value): ?array
    {
        $payload = json_decode($value, true);

        if (is_array($payload)) {
            return $this->validatePayload($payload) ? $payload : null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($value), true);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        return $this->validatePayload($payload) ? $payload : null;
    }

    protected function validatePayload(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        foreach (['version', 'policy_version', 'source', 'timestamp'] as $key) {
            if (! array_key_exists($key, $payload)) {
                return false;
            }
        }

        if (! is_string($payload['version']) || $payload['version'] === '') {
            return false;
        }

        if (! is_string($payload['policy_version']) || $payload['policy_version'] === '') {
            return false;
        }

        if (! is_int($payload['timestamp']) || $payload['timestamp'] < 0) {
            return false;
        }

        if (! is_string($payload['source']) || ConsentSource::tryFrom($payload['source']) === null) {
            return false;
        }

        foreach (['necessary', 'analytics', 'advertising', 'marketing_communications'] as $key) {
            if (! array_key_exists($key, $payload) || ! is_bool($payload[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Queue the first-party anonymous consent cookie. The plain-JSON payload is
     * encrypted by Laravel's EncryptCookies middleware on the way out, giving
     * authenticated encryption + tamper detection via APP_KEY.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function queueCookie(array $payload): void
    {
        Cookie::queue(Cookie::make(
            (string) config('consent.cookie_name', 'vanriti_consent'),
            $this->encodePayload($payload),
            (int) config('consent.cookie_lifetime', 365) * 1440,
            (string) config('consent.cookie_path', '/'),
            null,
            (bool) config('consent.cookie_secure', false),
            true,
            false,
            'lax',
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function encodePayload(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
