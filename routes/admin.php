<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CartWishlistController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DadiProductProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\MainCategoryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\ShipMojoController;
use App\Http\Controllers\Admin\WhatsAppBotController;
use App\Http\Controllers\Admin\WhatsAppCampaignController;
use App\Http\Controllers\Admin\WhatsAppInboxController;
use App\Http\Controllers\Admin\WhatsAppProfileController;
use App\Http\Controllers\Admin\WhatsAppTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['admin.auth'])->group(function () {
        Route::post('password/change', [AdminAuthController::class, 'changePassword'])->name('password.change');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:view-dashboard');

        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics')->middleware('permission:view-reports');

        Route::middleware('permission:manage-products')->group(function () {
            Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
        });

        Route::middleware('permission:view-products,manage-products')->group(function () {
            Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
            Route::get('products', [ProductController::class, 'index'])->name('products.index');
            Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        });

        Route::middleware('permission:manage-products')->group(function () {
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
            Route::post('products/{product}/images', [ProductController::class, 'storeImage'])->name('products.images.store');
            Route::delete('products/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
            Route::post('products/images/{image}/sort', [ProductController::class, 'sortImage'])->name('products.images.sort');
            Route::post('products/{product}/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
            Route::put('products/variants/{variant}', [ProductController::class, 'updateVariant'])->name('products.variants.update');
            Route::delete('products/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');
            Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
            Route::post('products/{product}/stock', [ProductController::class, 'updateStock'])->name('products.stock');
            Route::post('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
            Route::post('products/{product}/toggle-featured', [ProductController::class, 'toggleFeatured'])->name('products.toggle-featured');
        });

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index')->middleware('permission:view-categories,manage-categories');
        Route::get('main-categories', [MainCategoryController::class, 'index'])->name('main-categories.index')->middleware('permission:view-categories,manage-categories');

        Route::middleware('permission:manage-categories')->group(function () {
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::post('main-categories', [MainCategoryController::class, 'store'])->name('main-categories.store');
            Route::put('main-categories/{mainCategory}', [MainCategoryController::class, 'update'])->name('main-categories.update');
            Route::delete('main-categories/{mainCategory}', [MainCategoryController::class, 'destroy'])->name('main-categories.destroy');
        });

        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index')->middleware('permission:view-inventory,manage-inventory');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index')->middleware('permission:view-orders,manage-orders');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show')->middleware('permission:view-orders,manage-orders');

        Route::middleware('permission:manage-orders')->group(function () {
            Route::get('orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
            Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
            Route::post('orders/{order}/notes', [OrderController::class, 'addNote'])->name('orders.notes');
            // ShipMojo bulk actions
            Route::post('orders/bulk/push', [ShipMojoController::class, 'bulkPush'])->name('orders.shipmojo.bulk.push');
            Route::post('orders/bulk/auto-assign', [ShipMojoController::class, 'bulkAutoAssign'])->name('orders.shipmojo.bulk.auto-assign');
            Route::post('orders/bulk/schedule-pickup', [ShipMojoController::class, 'bulkSchedulePickup'])->name('orders.shipmojo.bulk.schedule-pickup');
            Route::post('orders/bulk/labels', [ShipMojoController::class, 'bulkLabels'])->name('orders.shipmojo.bulk.labels');
            Route::post('orders/bulk/cancel', [OrderController::class, 'bulkCancel'])->name('orders.bulk.cancel');
            // ShipMojo
            Route::post('orders/{order}/shipmojo/push', [ShipMojoController::class, 'pushOrder'])->name('orders.shipmojo.push');
            Route::post('orders/{order}/shipmojo/auto-assign', [ShipMojoController::class, 'autoAssign'])->name('orders.shipmojo.auto-assign');
            Route::post('orders/{order}/shipmojo/schedule-pickup', [ShipMojoController::class, 'schedulePickup'])->name('orders.shipmojo.schedule-pickup');
            Route::post('orders/{order}/shipmojo/cancel', [ShipMojoController::class, 'cancelShipment'])->name('orders.shipmojo.cancel');
            Route::get('orders/{order}/shipmojo/label', [ShipMojoController::class, 'getLabel'])->name('orders.shipmojo.label');
            Route::post('orders/{order}/shipmojo/track', [ShipMojoController::class, 'syncTracking'])->name('orders.shipmojo.track');
            Route::post('orders/{order}/send-recovery-whatsapp', [OrderController::class, 'sendRecoveryWhatsApp'])->name('orders.send-recovery-whatsapp');
            Route::post('orders/{order}/send-whatsapp', [OrderController::class, 'sendWhatsAppAlert'])->name('orders.send-whatsapp');
        });

        // Payment status is a financial action: it needs the dedicated
        // manage-payments permission and is audited via the activity log.
        Route::post('orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])
            ->name('orders.payment-status')
            ->middleware('permission:manage-payments');

        // Refunds
        Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index')->middleware('permission:view-refunds,manage-refunds');
        Route::middleware('permission:manage-refunds')->group(function () {
            Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
            Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
            Route::post('refunds/{refund}/process', [RefundController::class, 'process'])->name('refunds.process');
            Route::post('refunds/{refund}/complete', [RefundController::class, 'complete'])->name('refunds.complete');
        });

        // Returns
        Route::get('returns', [ReturnController::class, 'index'])->name('returns.index')->middleware('permission:view-returns,manage-returns');
        Route::get('returns/{returnRequest}', [ReturnController::class, 'show'])->name('returns.show')->middleware('permission:view-returns,manage-returns');
        Route::middleware('permission:manage-returns')->group(function () {
            Route::post('returns/{returnRequest}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
            Route::post('returns/{returnRequest}/reject', [ReturnController::class, 'reject'])->name('returns.reject');
            Route::post('returns/{returnRequest}/request-info', [ReturnController::class, 'requestInfo'])->name('returns.request-info');
            Route::post('returns/{returnRequest}/pickup', [ReturnController::class, 'markPickup'])->name('returns.pickup');
            Route::post('returns/{returnRequest}/complete', [ReturnController::class, 'markReturned'])->name('returns.complete');
            Route::post('returns/{returnRequest}/shipmojo/push', [ShipMojoController::class, 'pushReturnOrder'])->name('returns.shipmojo.push');
        });

        // Shipments
        Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index')->middleware('permission:view-shipments,manage-shipments');
        Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show')->middleware('permission:view-shipments,manage-shipments');
        Route::middleware('permission:manage-shipments')->group(function () {
            Route::post('shipments/{shipment}/tracking', [ShipmentController::class, 'addTrackingEvent'])->name('shipments.tracking');
        });

        // Customers & Carts
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index')->middleware('permission:view-customers,manage-customers');
        Route::get('customers/{user}', [CustomerController::class, 'show'])->name('customers.show')->middleware('permission:view-customers,manage-customers');
        Route::get('carts', [CartWishlistController::class, 'carts'])->name('carts.index')->middleware('permission:view-customers,manage-customers');
        Route::get('wishlists', [CartWishlistController::class, 'wishlists'])->name('wishlists.index')->middleware('permission:view-customers,manage-customers');
        Route::middleware('permission:manage-customers')->group(function () {
            Route::post('customers/{user}/toggle', [CustomerController::class, 'toggleStatus'])->name('customers.toggle');
            Route::post('customers/{user}/send-whatsapp', [CustomerController::class, 'sendWhatsApp'])->name('customers.send-whatsapp');
            Route::post('carts/{cart}/send-recovery-whatsapp', [CartWishlistController::class, 'sendRecoveryWhatsApp'])->name('carts.send-recovery-whatsapp');
        });

        // Coupons
        Route::get('coupons', [CouponController::class, 'index'])->name('coupons.index')->middleware('permission:view-coupons,manage-coupons');
        Route::middleware('permission:manage-coupons')->group(function () {
            Route::get('coupons/create', [CouponController::class, 'create'])->name('coupons.create');
            Route::post('coupons', [CouponController::class, 'store'])->name('coupons.store');
            Route::get('coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
            Route::put('coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
            Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
        });

        // Reviews
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index')->middleware('permission:view-reviews,manage-reviews');
        Route::middleware('permission:manage-reviews')->group(function () {
            Route::post('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
            Route::post('reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
            Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
        });

        // Content CMS
        Route::get('banners', [BannerController::class, 'index'])->name('banners.index')->middleware('permission:view-banners,manage-banners');
        Route::middleware('permission:manage-banners')->group(function () {
            Route::get('banners/create', [BannerController::class, 'create'])->name('banners.create');
            Route::post('banners', [BannerController::class, 'store'])->name('banners.store');
            Route::get('banners/{banner}/edit', [BannerController::class, 'edit'])->name('banners.edit');
            Route::put('banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
            Route::delete('banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
        });

        Route::get('media', [MediaController::class, 'index'])->name('media.index')->middleware('permission:view-media,manage-media');
        Route::get('media/export', [MediaController::class, 'export'])->name('media.export')->middleware('permission:view-media,manage-media');
        Route::middleware('permission:manage-media')->group(function () {
            Route::post('media', [MediaController::class, 'store'])->name('media.store');
            Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
        });

        Route::get('blogs', [BlogController::class, 'index'])->name('blogs.index')->middleware('permission:view-blogs,manage-blogs');
        Route::middleware('permission:manage-blogs')->group(function () {
            Route::get('blogs/create', [BlogController::class, 'create'])->name('blogs.create');
            Route::post('blogs', [BlogController::class, 'store'])->name('blogs.store');
            Route::get('blogs/{blog}/edit', [BlogController::class, 'edit'])->name('blogs.edit');
            Route::put('blogs/{blog}', [BlogController::class, 'update'])->name('blogs.update');
            Route::delete('blogs/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');
        });

        Route::get('pages', [PageController::class, 'index'])->name('pages.index')->middleware('permission:view-pages,manage-pages');
        Route::middleware('permission:manage-pages')->group(function () {
            Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
            Route::post('pages', [PageController::class, 'store'])->name('pages.store');
            Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
            Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');
            Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
        });

        Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index')->middleware('permission:view-faqs,manage-faqs');
        Route::middleware('permission:manage-faqs')->group(function () {
            Route::get('faqs/create', [FaqController::class, 'create'])->name('faqs.create');
            Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
            Route::get('faqs/{faq}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
            Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
            Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
        });

        Route::middleware('permission:manage-newsletters')->group(function () {
            Route::get('newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');
            Route::delete('newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');
            Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
            Route::get('contact-messages/{message}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
            Route::delete('contact-messages/{message}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');
        });

        Route::resource('admins', AdminUserController::class)->except(['show'])->middleware('permission:manage-admins');
        Route::middleware('permission:manage-roles')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });

        Route::get('activities', [ActivityLogController::class, 'index'])->name('activities.index')->middleware('permission:view-activities,manage-activities');

        Route::middleware('permission:review-dadi-product-profiles')->group(function () {
            Route::get('dadi/product-profiles', [DadiProductProfileController::class, 'index'])->name('dadi.product-profiles.index');
            Route::get('dadi/product-profiles/create', [DadiProductProfileController::class, 'create'])->name('dadi.product-profiles.create');
            Route::post('dadi/product-profiles', [DadiProductProfileController::class, 'store'])->name('dadi.product-profiles.store');
            Route::get('dadi/product-profiles/{profile}/edit', [DadiProductProfileController::class, 'edit'])->name('dadi.product-profiles.edit');
            Route::put('dadi/product-profiles/{profile}', [DadiProductProfileController::class, 'update'])->name('dadi.product-profiles.update');
            Route::post('dadi/product-profiles/{profile}/submit-review', [DadiProductProfileController::class, 'submitReview'])->name('dadi.product-profiles.submit-review');
            Route::post('dadi/product-profiles/{profile}/approve', [DadiProductProfileController::class, 'approve'])->name('dadi.product-profiles.approve');
            Route::post('dadi/product-profiles/{profile}/reject', [DadiProductProfileController::class, 'reject'])->name('dadi.product-profiles.reject');
        });

        Route::middleware('permission:manage-settings')->group(function () {
            Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
        });

        // ShipMojo utilities (requires manage-shipments or manage-settings)
        Route::middleware('permission:manage-shipments,manage-settings')->group(function () {
            Route::get('shipmojo/ping', [ShipMojoController::class, 'ping'])->name('shipmojo.ping');
            Route::get('shipmojo/warehouses', [ShipMojoController::class, 'warehouses'])->name('shipmojo.warehouses');
            Route::post('shipmojo/rates', [ShipMojoController::class, 'rates'])->name('shipmojo.rates');
        });

        // WhatsApp CRM & Live Chat Inbox
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            // Live Chat Inbox (Read / View / Poll)
            Route::get('inbox', [WhatsAppInboxController::class, 'index'])
                ->name('inbox')
                ->middleware('permission:view-whatsapp-inbox,manage-whatsapp-inbox');
            Route::get('inbox/{conversation}/poll', [WhatsAppInboxController::class, 'poll'])
                ->name('inbox.poll')
                ->middleware('permission:view-whatsapp-inbox,manage-whatsapp-inbox');
            Route::get('inbox-poll-conversations', [WhatsAppInboxController::class, 'pollConversations'])
                ->name('inbox.poll-conversations')
                ->middleware('permission:view-whatsapp-inbox,manage-whatsapp-inbox');
            Route::get('media/{message}', [WhatsAppInboxController::class, 'streamMedia'])
                ->name('media.download')
                ->middleware('permission:view-whatsapp-inbox,manage-whatsapp-inbox');

            // Live Chat Inbox (Reply, Send Media, Assign, Note)
            Route::middleware('permission:manage-whatsapp-inbox')->group(function () {
                Route::post('inbox/{conversation}/send', [WhatsAppInboxController::class, 'sendText'])->name('inbox.send');
                Route::post('inbox/{conversation}/send-media', [WhatsAppInboxController::class, 'sendMedia'])->name('inbox.send-media');
                Route::post('inbox/{conversation}/send-template', [WhatsAppInboxController::class, 'sendTemplate'])->name('inbox.send-template');
                Route::post('inbox/{conversation}/read', [WhatsAppInboxController::class, 'markRead'])->name('inbox.read');
                Route::post('inbox/{conversation}/assign', [WhatsAppInboxController::class, 'assign'])->name('inbox.assign');
                Route::post('inbox/{conversation}/status', [WhatsAppInboxController::class, 'updateStatus'])->name('inbox.status');
                Route::post('inbox/{conversation}/note', [WhatsAppInboxController::class, 'addNote'])->name('inbox.note');
            });

            // Broadcast Campaigns (Phase 4)
            Route::get('campaigns', [WhatsAppCampaignController::class, 'index'])
                ->name('campaigns.index')
                ->middleware('permission:view-whatsapp-campaigns,manage-whatsapp-campaigns');
            Route::get('campaigns/create', [WhatsAppCampaignController::class, 'create'])
                ->name('campaigns.create')
                ->middleware('permission:manage-whatsapp-campaigns');
            Route::post('campaigns', [WhatsAppCampaignController::class, 'store'])
                ->name('campaigns.store')
                ->middleware('permission:manage-whatsapp-campaigns');
            Route::get('campaigns/{campaign}', [WhatsAppCampaignController::class, 'show'])
                ->name('campaigns.show')
                ->middleware('permission:view-whatsapp-campaigns,manage-whatsapp-campaigns');
            Route::post('campaigns/test-send', [WhatsAppCampaignController::class, 'sendTest'])
                ->name('campaigns.test-send')
                ->middleware('permission:manage-whatsapp-campaigns');
            Route::post('campaigns/{campaign}/dispatch', [WhatsAppCampaignController::class, 'dispatchCampaign'])
                ->name('campaigns.dispatch')
                ->middleware('permission:manage-whatsapp-campaigns');

            // Business Profile & Health (Phase 5)
            Route::middleware('permission:manage-whatsapp-profile')->group(function () {
                Route::get('profile', [WhatsAppProfileController::class, 'index'])->name('profile');
                Route::post('profile', [WhatsAppProfileController::class, 'update'])->name('profile.update');
                Route::post('profile/sync', [WhatsAppProfileController::class, 'sync'])->name('profile.sync');
            });

            // Chatbot & Quick Replies (Phase 6)
            Route::get('bot/quick-replies/api', [WhatsAppBotController::class, 'apiQuickReplies'])
                ->name('bot.quick-replies.api')
                ->middleware('permission:view-whatsapp-inbox,manage-whatsapp-inbox,manage-whatsapp-bot');

            Route::middleware('permission:manage-whatsapp-bot')->group(function () {
                Route::get('bot', [WhatsAppBotController::class, 'index'])->name('bot.index');
                Route::post('bot/rules', [WhatsAppBotController::class, 'storeRule'])->name('bot.rules.store');
                Route::put('bot/rules/{rule}', [WhatsAppBotController::class, 'updateRule'])->name('bot.rules.update');
                Route::post('bot/rules/{rule}/toggle', [WhatsAppBotController::class, 'toggleRule'])->name('bot.rules.toggle');
                Route::delete('bot/rules/{rule}', [WhatsAppBotController::class, 'destroyRule'])->name('bot.rules.destroy');

                Route::post('bot/quick-replies', [WhatsAppBotController::class, 'storeQuickReply'])->name('bot.quick-replies.store');
                Route::put('bot/quick-replies/{quickReply}', [WhatsAppBotController::class, 'updateQuickReply'])->name('bot.quick-replies.update');
                Route::delete('bot/quick-replies/{quickReply}', [WhatsAppBotController::class, 'destroyQuickReply'])->name('bot.quick-replies.destroy');
            });

            // Meta Templates Manager (Phase 7)
            Route::get('templates/api/approved', [WhatsAppTemplateController::class, 'apiApproved'])
                ->name('templates.api.approved')
                ->middleware('permission:view-whatsapp-inbox,manage-whatsapp-inbox,manage-whatsapp-campaigns,manage-whatsapp-templates');

            Route::middleware('permission:manage-whatsapp-templates')->group(function () {
                Route::get('templates', [WhatsAppTemplateController::class, 'index'])->name('templates.index');
                Route::post('templates/sync', [WhatsAppTemplateController::class, 'sync'])->name('templates.sync');
                Route::post('templates/waba-id', [WhatsAppTemplateController::class, 'updateWabaId'])->name('templates.waba-id');
                Route::get('templates/create', [WhatsAppTemplateController::class, 'create'])->name('templates.create');
                Route::post('templates', [WhatsAppTemplateController::class, 'store'])->name('templates.store');
                Route::delete('templates/{template}', [WhatsAppTemplateController::class, 'destroy'])->name('templates.destroy');
            });
        });
    });
});
