<?php

namespace Tests\Feature;

use App\Consent\ConsentService;
use App\Consent\Enums\ConsentSource;
use App\Models\ConsentChoice;
use App\Models\ConsentPreference;
use App\Models\User;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ConsentTest extends TestCase
{
    use RefreshDatabase;

    protected const COOKIE = 'vanriti_consent';

    protected function setUp(): void
    {
        parent::setUp();

        // We send consent cookies already in the encrypted wire format (what
        // EncryptCookies produces on the way out), so stop the testing client
        // from re-encrypting them before dispatch.
        $this->disableCookieEncryption();

        Route::middleware('web')->group(function () {
            Route::get('consent-test/resolve', function (Request $request) {
                $consent = app(ConsentService::class);

                return response()->json($consent->resolve()->toArray());
            });

            Route::post('consent-test/update', function (Request $request) {
                $consent = app(ConsentService::class);
                $consent->updateAnonymous($request->input('categories', []), ConsentSource::Banner);

                return response()->json($consent->resolve()->toArray());
            });

            Route::post('consent-test/withdraw', function (Request $request) {
                $consent = app(ConsentService::class);
                $consent->withdrawAnonymous($request->input('categories', []), ConsentSource::Settings);

                return response()->json($consent->resolve()->toArray());
            });
        });
    }

    /**
     * Payload array as the service would persist it (used for building cookies).
     */
    protected function payloadFor(array $overrides = []): array
    {
        return array_merge([
            'version' => config('consent.version'),
            'policy_version' => config('consent.policy_version'),
            'necessary' => true,
            'analytics' => false,
            'advertising' => false,
            'marketing_communications' => false,
            'source' => ConsentSource::Banner->value,
            'timestamp' => time(),
        ], $overrides);
    }

    /**
     * Build a cookie value the way EncryptCookies produces it on the way out:
     * prefixed, encrypted, not serialized. It therefore round-trips through the
     * middleware exactly like a browser-presented cookie.
     */
    protected function cookieFor(array $overrides = []): string
    {
        $encrypter = app('encrypter');

        return $encrypter->encrypt(
            CookieValuePrefix::create(static::COOKIE, $encrypter->getKey()).json_encode($this->payloadFor($overrides), JSON_THROW_ON_ERROR),
            EncryptCookies::serialized(static::COOKIE),
        );
    }

    /**
     * Plain-encrypted payload (no prefix). Only useful for service-level calls
     * that read the cookie straight off the request (no EncryptCookies pass).
     */
    protected function plainEncryptedPayload(array $overrides = []): string
    {
        return Crypt::encryptString(json_encode($this->payloadFor($overrides), JSON_THROW_ON_ERROR));
    }

    protected function presentCookie(string $value): void
    {
        request()->cookies->set(static::COOKIE, $value);
    }

    protected function cookieFrom(TestResponse $response): string
    {
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === static::COOKIE);

        $this->assertNotNull($cookie, 'Expected a queued consent cookie in the response.');

        return $cookie->getValue();
    }

    public function test_guest_without_cookie_resolves_to_safe_defaults(): void
    {
        $this->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'necessary' => true,
                'analytics' => false,
                'advertising' => false,
                'marketing_communications' => false,
                'authenticated' => false,
                'requires_reconsent' => false,
                'source' => ConsentSource::System->value,
            ]);
    }

    public function test_anonymous_update_grants_categories_and_round_trips_via_cookie(): void
    {
        $response = $this->post('consent-test/update', [
            'categories' => [
                'analytics' => true,
                'advertising' => true,
                'marketing_communications' => false,
            ],
        ])->assertJson([
            'necessary' => true,
            'analytics' => true,
            'advertising' => true,
            'marketing_communications' => false,
        ]);

        $cookie = $this->cookieFrom($response);

        $this->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'necessary' => true,
                'analytics' => false,
                'advertising' => false,
                'marketing_communications' => false,
                'authenticated' => false,
            ]);

        $this->withCookie(static::COOKIE, $cookie)
            ->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'necessary' => true,
                'analytics' => true,
                'advertising' => true,
                'marketing_communications' => false,
                'authenticated' => false,
                'source' => ConsentSource::Banner->value,
            ]);
    }

    public function test_anonymous_cookie_is_encrypted_and_has_secure_attributes(): void
    {
        $response = $this->post('consent-test/update', [
            'categories' => ['analytics' => true],
        ]);

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === static::COOKIE);

        $this->assertNotNull($cookie);
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertSame((bool) config('consent.cookie_secure', false), $cookie->isSecure());
        $this->assertGreaterThan(time() + 360 * 24 * 3600, $cookie->getExpiresTime());

        $encoded = $cookie->getValue();
        $this->assertStringNotContainsString('analytics', $encoded);
        $this->assertStringNotContainsString('necessary', $encoded);
        $this->assertNotEmpty($encoded);
    }

    public function test_tampered_cookie_fails_closed_over_http(): void
    {
        $this->withCookie(static::COOKIE, 'not-an-encrypted-consent-cookie')
            ->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'necessary' => true,
                'analytics' => false,
                'advertising' => false,
                'marketing_communications' => false,
                'requires_reconsent' => false,
            ]);
    }

    public function test_invalid_encrypted_cookie_fails_closed_over_http(): void
    {
        $this->withCookie(static::COOKIE, Crypt::encryptString('not-a-valid-prefixed-cookie'))
            ->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'necessary' => true,
                'analytics' => false,
                'advertising' => false,
                'marketing_communications' => false,
            ]);
    }

    public function test_encrypted_non_json_payload_fails_closed(): void
    {
        $encrypter = app('encrypter');
        $payload = $encrypter->encrypt(
            CookieValuePrefix::create(static::COOKIE, $encrypter->getKey()).'garbage-not-json',
            EncryptCookies::serialized(static::COOKIE),
        );

        $this->withCookie(static::COOKIE, $payload)
            ->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'necessary' => true,
                'analytics' => false,
                'advertising' => false,
                'requires_reconsent' => false,
            ]);
    }

    public function test_unknown_source_in_payload_fails_closed(): void
    {
        $this->withCookie(static::COOKIE, $this->cookieFor([
            'analytics' => true,
            'source' => 'uncontrolled_client_value',
        ]))->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'analytics' => false,
                'advertising' => false,
            ]);
    }

    public function test_stale_version_revokes_optional_and_marks_reconsent_required(): void
    {
        $this->withCookie(static::COOKIE, $this->cookieFor([
            'analytics' => true,
            'advertising' => true,
            'version' => '2024-01-01',
        ]))->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'necessary' => true,
                'analytics' => false,
                'advertising' => false,
                'requires_reconsent' => true,
            ]);
    }

    public function test_source_is_preserved_through_cookie_round_trip(): void
    {
        $response = $this->post('consent-test/withdraw', [
            'categories' => ['analytics'],
        ]);

        $this->withCookie(static::COOKIE, $this->cookieFrom($response))
            ->get('consent-test/resolve')
            ->assertOk()
            ->assertJson(['source' => ConsentSource::Settings->value]);
    }

    public function test_authenticated_user_without_preferences_resolves_to_defaults(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'necessary' => true,
                'analytics' => false,
                'advertising' => false,
                'marketing_communications' => false,
                'authenticated' => true,
            ]);
    }

    public function test_authenticated_preferences_are_authoritative_over_cookie(): void
    {
        $user = User::factory()->create();

        ConsentPreference::create([
            'user_id' => $user->id,
            'analytics' => true,
            'advertising' => false,
            'marketing_communications' => true,
            'consent_version' => config('consent.version'),
            'policy_version' => config('consent.policy_version'),
            'source' => ConsentSource::Banner->value,
            'consented_at' => now(),
        ]);

        // A stale/different cookie must not matter for an authenticated user.
        $this->withCookie(static::COOKIE, $this->cookieFor(['advertising' => true]))
            ->actingAs($user, 'web')
            ->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'analytics' => true,
                'advertising' => false,
                'marketing_communications' => true,
                'authenticated' => true,
            ]);
    }

    public function test_update_keeps_one_preference_row_per_user(): void
    {
        $user = User::factory()->create();
        $service = app(ConsentService::class);

        $service->update($user, ['analytics' => true], ConsentSource::Banner);
        $service->update($user, ['analytics' => true, 'advertising' => true], ConsentSource::Settings);

        $this->assertSame(1, ConsentPreference::where('user_id', $user->id)->count());
        $this->assertSame(2, ConsentChoice::where('user_id', $user->id)->count());

        $preference = ConsentPreference::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($preference->analytics);
        $this->assertTrue($preference->advertising);
        $this->assertFalse($preference->marketing_communications);
    }

    public function test_update_appends_append_only_history(): void
    {
        $user = User::factory()->create();
        $service = app(ConsentService::class);

        $service->update($user, ['analytics' => true], ConsentSource::Banner);
        $service->update($user, ['analytics' => false], ConsentSource::Settings);

        $history = ConsentChoice::where('user_id', $user->id)->orderBy('id')->get();

        $this->assertCount(2, $history);
        $this->assertTrue((bool) $history[0]->analytics);
        $this->assertFalse((bool) $history[1]->analytics);
        $this->assertSame(ConsentSource::Banner->value, $history[0]->source);
        $this->assertSame(ConsentSource::Settings->value, $history[1]->source);
        $this->assertSame(config('consent.version'), $history[0]->consent_version);
        $this->assertNotNull($history[0]->created_at);

        // History records are never touched by later preference changes.
        $this->assertSame(2, ConsentChoice::where('user_id', $user->id)->count());
        $preference = ConsentPreference::where('user_id', $user->id)->firstOrFail();
        $this->assertFalse((bool) $preference->analytics);
    }

    public function test_history_carries_no_pii(): void
    {
        $columns = Schema::getColumnListing('consent_choices');

        $this->assertNotContains('ip_address', $columns);
        $this->assertNotContains('user_agent', $columns);
        $this->assertNotContains('email', $columns);
        $this->assertNotContains('name', $columns);
    }

    public function test_update_forces_necessary_true_and_ignores_unknown_categories(): void
    {
        $user = User::factory()->create();
        $service = app(ConsentService::class);

        $service->update(
            $user,
            ['necessary' => false, 'analytics' => true, 'invented_category' => true],
            ConsentSource::Banner,
        );

        $preference = ConsentPreference::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($preference->necessary);
        $this->assertTrue($preference->analytics);
        $this->assertFalse($preference->advertising);
        $this->assertFalse($preference->marketing_communications);
        $this->assertSame(ConsentSource::Banner->value, $preference->source);
    }

    public function test_withdraw_all_denies_every_optional_category(): void
    {
        $user = User::factory()->create();
        $service = app(ConsentService::class);

        $service->update($user, [
            'analytics' => true,
            'advertising' => true,
            'marketing_communications' => true,
        ], ConsentSource::Banner);

        $state = $service->withdraw($user);

        $this->assertFalse($state->analytics());
        $this->assertFalse($state->advertising());
        $this->assertFalse($state->marketingCommunications());
        $this->assertTrue($state->necessary());

        $history = ConsentChoice::where('user_id', $user->id)->orderByDesc('id')->first();
        $this->assertSame(ConsentSource::Settings->value, $history->source);
        $this->assertFalse((bool) $history->analytics);

        $this->assertSame(1, ConsentPreference::where('user_id', $user->id)->count());
    }

    public function test_withdraw_specific_categories_keeps_the_rest(): void
    {
        $user = User::factory()->create();
        $service = app(ConsentService::class);

        $service->update($user, ['analytics' => true, 'advertising' => true], ConsentSource::Banner);

        $service->withdraw($user, ['analytics']);

        $preference = ConsentPreference::where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($preference->analytics);
        $this->assertTrue($preference->advertising);
    }

    public function test_withdraw_anonymous_specific_categories_keeps_the_rest(): void
    {
        $response = $this->withCookie(static::COOKIE, $this->cookieFor([
            'analytics' => true,
            'advertising' => true,
        ]))->post('consent-test/withdraw', [
            'categories' => ['analytics'],
        ])->assertJson([
            'analytics' => false,
            'advertising' => true,
            'necessary' => true,
        ]);

        $this->withCookie(static::COOKIE, $this->cookieFrom($response))
            ->get('consent-test/resolve')
            ->assertOk()
            ->assertJson([
                'analytics' => false,
                'advertising' => true,
            ]);
    }

    public function test_merge_case_a_carries_anonymous_choices_forward(): void
    {
        $user = User::factory()->create();
        $this->presentCookie($this->plainEncryptedPayload([
            'analytics' => true,
            'advertising' => true,
            'marketing_communications' => false,
        ]));

        $service = app(ConsentService::class);
        $this->assertTrue($service->mergeAnonymousIntoUser($user));

        $preference = ConsentPreference::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($preference->analytics);
        $this->assertTrue($preference->advertising);
        $this->assertFalse($preference->marketing_communications);
        $this->assertTrue($preference->necessary);
        $this->assertSame(ConsentSource::CarriedFromAnonymous->value, $preference->source);
        $this->assertSame(config('consent.version'), $preference->consent_version);

        $history = ConsentChoice::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(ConsentSource::CarriedFromAnonymous->value, $history->source);
        $this->assertTrue((bool) $history->analytics);
    }

    public function test_merge_case_b_existing_preferences_win(): void
    {
        $user = User::factory()->create();

        ConsentPreference::create([
            'user_id' => $user->id,
            'analytics' => false,
            'consent_version' => config('consent.version'),
            'policy_version' => config('consent.policy_version'),
            'source' => ConsentSource::Banner->value,
            'consented_at' => now(),
        ]);

        $this->presentCookie($this->plainEncryptedPayload(['analytics' => true]));

        $service = app(ConsentService::class);
        $this->assertFalse($service->mergeAnonymousIntoUser($user));

        $preference = ConsentPreference::where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($preference->analytics);
        $this->assertSame(ConsentSource::Banner->value, $preference->source);
        $this->assertSame(1, ConsentPreference::where('user_id', $user->id)->count());
        $this->assertSame(0, ConsentChoice::where('user_id', $user->id)->count());
    }

    public function test_merge_case_c_denied_anonymous_consent_is_never_upgraded(): void
    {
        $user = User::factory()->create();
        $this->presentCookie($this->plainEncryptedPayload([
            'analytics' => false,
            'advertising' => false,
            'marketing_communications' => false,
        ]));

        $service = app(ConsentService::class);
        $this->assertTrue($service->mergeAnonymousIntoUser($user));

        $preference = ConsentPreference::where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($preference->analytics);
        $this->assertFalse($preference->advertising);
        $this->assertFalse($preference->marketing_communications);
        $this->assertTrue($preference->necessary);
    }

    public function test_merge_requires_current_version(): void
    {
        $user = User::factory()->create();
        $this->presentCookie($this->plainEncryptedPayload([
            'analytics' => true,
            'version' => '2024-01-01',
        ]));

        $service = app(ConsentService::class);
        $this->assertFalse($service->mergeAnonymousIntoUser($user));
        $this->assertSame(0, ConsentPreference::where('user_id', $user->id)->count());
        $this->assertSame(0, ConsentChoice::where('user_id', $user->id)->count());
    }

    public function test_merge_is_idempotent(): void
    {
        $user = User::factory()->create();
        $this->presentCookie($this->plainEncryptedPayload(['analytics' => true]));

        $service = app(ConsentService::class);
        $this->assertTrue($service->mergeAnonymousIntoUser($user));
        $this->assertFalse($service->mergeAnonymousIntoUser($user));

        $this->assertSame(1, ConsentPreference::where('user_id', $user->id)->count());
        $this->assertSame(1, ConsentChoice::where('user_id', $user->id)->count());
    }

    public function test_merge_noops_without_cookie_or_with_garbage(): void
    {
        $noCookieUser = User::factory()->create();
        $garbageUser = User::factory()->create();

        $service = app(ConsentService::class);
        $this->assertFalse($service->mergeAnonymousIntoUser($noCookieUser));

        $this->presentCookie('garbage-not-a-valid-payload');
        $this->assertFalse($service->mergeAnonymousIntoUser($garbageUser));

        $this->assertSame(0, ConsentPreference::where('user_id', $noCookieUser->id)->count());
        $this->assertSame(0, ConsentPreference::where('user_id', $garbageUser->id)->count());
    }

    public function test_login_carries_anonymous_consent_forward(): void
    {
        $user = User::factory()->create([
            'email' => 'consent.login@example.com',
            'password' => 'password123',
        ]);

        $this->withCookie(static::COOKIE, $this->cookieFor([
            'analytics' => true,
        ]))->post(route('login.submit'), [
            'email' => 'consent.login@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertDatabaseHas('consent_preferences', [
            'user_id' => $user->id,
            'analytics' => true,
            'source' => ConsentSource::CarriedFromAnonymous->value,
        ]);

        $this->assertDatabaseHas('consent_choices', [
            'user_id' => $user->id,
            'analytics' => true,
            'source' => ConsentSource::CarriedFromAnonymous->value,
        ]);
    }

    public function test_registration_carries_anonymous_consent_forward(): void
    {
        $this->withCookie(static::COOKIE, $this->cookieFor([
            'analytics' => true,
            'marketing_communications' => true,
        ]))->post(route('register.submit'), [
            'name' => 'Consent Register User',
            'email' => 'consent.register@example.com',
            'phone' => '9876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('account.dashboard'));

        $user = User::where('email', 'consent.register@example.com')->firstOrFail();

        $this->assertDatabaseHas('consent_preferences', [
            'user_id' => $user->id,
            'analytics' => true,
            'marketing_communications' => true,
            'source' => ConsentSource::CarriedFromAnonymous->value,
        ]);
    }

    public function test_login_without_anonymous_consent_keeps_account_consent_untouched(): void
    {
        $user = User::factory()->create([
            'email' => 'consent.blank@example.com',
            'password' => 'password123',
        ]);

        $this->post(route('login.submit'), [
            'email' => 'consent.blank@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertSame(0, ConsentPreference::where('user_id', $user->id)->count());
        $this->assertSame(0, ConsentChoice::where('user_id', $user->id)->count());
    }
}
