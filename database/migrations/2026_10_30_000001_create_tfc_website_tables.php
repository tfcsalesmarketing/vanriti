<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for TFC Sales & Marketing website and content management.
     */
    public function up(): void
    {
        // 1. TFC Website Settings
        if (! Schema::hasTable('tfc_settings')) {
            Schema::create('tfc_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->default('general')->index();
                $table->timestamps();
            });
        }

        // 2. TFC Pages (CMS Pages with default 'draft' to safeguard unconfirmed policies)
        if (! Schema::hasTable('tfc_pages')) {
            Schema::create('tfc_pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content')->nullable();
                $table->text('excerpt')->nullable();
                $table->string('featured_image')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft')->index();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 3. TFC Blogs & Corporate Updates
        if (! Schema::hasTable('tfc_blogs')) {
            Schema::create('tfc_blogs', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('featured_image')->nullable();
                $table->text('excerpt')->nullable();
                $table->longText('content');
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft')->index();
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamps();
            });
        }

        // 4. TFC Homepage Banners & Opportunity CTAs
        if (! Schema::hasTable('tfc_banners')) {
            Schema::create('tfc_banners', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('subtitle')->nullable();
                $table->string('image');
                $table->string('mobile_image')->nullable();
                $table->string('link')->nullable();
                $table->string('link_text')->nullable();
                $table->enum('position', ['hero', 'opportunity', 'cta'])->default('hero')->index();
                $table->integer('sort_order')->default(0);
                $table->enum('status', ['active', 'inactive'])->default('active')->index();
                $table->timestamps();
            });
        }

        // 5. TFC Header & Footer Navigation Menus
        if (! Schema::hasTable('tfc_menus')) {
            Schema::create('tfc_menus', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('url');
                $table->foreignId('parent_id')->nullable()->constrained('tfc_menus')->nullOnDelete();
                $table->enum('location', ['header', 'footer_col1', 'footer_col2', 'footer_legal'])->default('header')->index();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('open_new_tab')->default(false);
                $table->timestamps();
            });
        }

        // 6. TFC Inquiries & Prospective Direct-Seller Applications
        if (! Schema::hasTable('tfc_inquiries')) {
            Schema::create('tfc_inquiries', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone', 30);
                $table->string('subject')->nullable();
                $table->text('message');
                $table->enum('type', ['general', 'seller_application'])->default('general')->index();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->enum('status', ['new', 'in_progress', 'resolved'])->default('new')->index();
                $table->text('notes')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        // 7. Statutory Consumer Grievance Tickets (Rule 7, Direct Selling Rules 2021)
        if (! Schema::hasTable('tfc_grievance_tickets')) {
            Schema::create('tfc_grievance_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number', 50)->unique();
                $table->string('consumer_name');
                $table->string('email');
                $table->string('phone', 30);
                $table->string('order_number')->nullable();
                $table->enum('category', ['product_defect', 'delivery_delay', 'direct_seller_conduct', 'refund_issue', 'other'])->default('other');
                $table->text('complaint_details');
                $table->enum('status', ['acknowledged', 'under_investigation', 'resolved'])->default('acknowledged')->index();
                $table->text('resolution_notes')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations (Drops strictly only TFC-created tables).
     */
    public function down(): void
    {
        Schema::dropIfExists('tfc_grievance_tickets');
        Schema::dropIfExists('tfc_inquiries');
        Schema::dropIfExists('tfc_menus');
        Schema::dropIfExists('tfc_banners');
        Schema::dropIfExists('tfc_blogs');
        Schema::dropIfExists('tfc_pages');
        Schema::dropIfExists('tfc_settings');
    }
};
