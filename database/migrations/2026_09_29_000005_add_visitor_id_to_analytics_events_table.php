<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the persistent anonymous visitor identifier to analytics_events.
     *
     * visitor_id is a cryptographically random UUID held in a first-party,
     * HttpOnly cookie. It is deliberately decoupled from session_id:
     *
     *   visitor_id = one browser identity across many sessions
     *   session_id = one browsing session (existing HMAC of the Laravel session)
     *
     * This is what makes "unique visitors", "new visitors" and "returning
     * visitors" answerable, which a per-session identifier cannot do. No PII is
     * stored: no name, email, phone, address, IP address or device fingerprint,
     * and the value is never sent to Meta, GA4 or Google Ads.
     *
     * Nullable because events recorded before this migration — and events from
     * legitimate server-side contexts with no browser request — have no
     * visitor. Absence must never be an error.
     */
    public function up(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->char('visitor_id', 36)->nullable()->after('session_id');

            // Serves both hot paths: "distinct visitors in period" (visitor_id
            // + occurred_at range scan) and the historical first-seen lookup
            // (MIN(occurred_at) GROUP BY visitor_id).
            $table->index(['visitor_id', 'occurred_at'], 'analytics_events_visitor_id_occurred_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex('analytics_events_visitor_id_occurred_at_index');
            $table->dropColumn('visitor_id');
        });
    }
};
