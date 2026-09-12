<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CategoryController;
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
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['admin.auth'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:view-dashboard');

        Route::middleware('permission:manage-products')->group(function () {
            Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
            Route::resource('products', ProductController::class)->except(['destroy']);
            Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
            Route::post('products/{product}/images', [ProductController::class, 'storeImage'])->name('products.images.store');
            Route::delete('products/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
            Route::post('products/images/{image}/sort', [ProductController::class, 'sortImage'])->name('products.images.sort');
            Route::post('products/{product}/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
            Route::put('products/variants/{variant}', [ProductController::class, 'updateVariant'])->name('products.variants.update');
            Route::delete('products/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');
            Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
            Route::post('products/{product}/stock', [ProductController::class, 'updateStock'])->name('products.stock');
        });

        Route::resource('categories', CategoryController::class)->except(['show'])->middleware('permission:manage-categories');
        Route::resource('main-categories', MainCategoryController::class)->except(['show'])->middleware('permission:manage-categories');
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index')->middleware('permission:manage-inventory');

        Route::middleware('permission:manage-orders')->group(function () {
            Route::resource('orders', OrderController::class)->except(['create', 'store', 'destroy']);
            Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
            Route::post('orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('orders.payment-status');
            Route::post('orders/{order}/notes', [OrderController::class, 'addNote'])->name('orders.notes');
            // ShipMojo
            Route::post('orders/{order}/shipmojo/push', [ShipMojoController::class, 'pushOrder'])->name('orders.shipmojo.push');
            Route::post('orders/{order}/shipmojo/auto-assign', [ShipMojoController::class, 'autoAssign'])->name('orders.shipmojo.auto-assign');
            Route::post('orders/{order}/shipmojo/schedule-pickup', [ShipMojoController::class, 'schedulePickup'])->name('orders.shipmojo.schedule-pickup');
            Route::post('orders/{order}/shipmojo/cancel', [ShipMojoController::class, 'cancelShipment'])->name('orders.shipmojo.cancel');
            Route::get('orders/{order}/shipmojo/label', [ShipMojoController::class, 'getLabel'])->name('orders.shipmojo.label');
            Route::post('orders/{order}/shipmojo/track', [ShipMojoController::class, 'syncTracking'])->name('orders.shipmojo.track');
        });

        Route::middleware('permission:manage-refunds')->group(function () {
            Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
            Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
            Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
            Route::post('refunds/{refund}/process', [RefundController::class, 'process'])->name('refunds.process');
            Route::post('refunds/{refund}/complete', [RefundController::class, 'complete'])->name('refunds.complete');
        });

        Route::middleware('permission:manage-returns')->group(function () {
            Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
            Route::get('returns/{returnRequest}', [ReturnController::class, 'show'])->name('returns.show');
            Route::post('returns/{returnRequest}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
            Route::post('returns/{returnRequest}/reject', [ReturnController::class, 'reject'])->name('returns.reject');
            Route::post('returns/{returnRequest}/request-info', [ReturnController::class, 'requestInfo'])->name('returns.request-info');
            Route::post('returns/{returnRequest}/pickup', [ReturnController::class, 'markPickup'])->name('returns.pickup');
            Route::post('returns/{returnRequest}/complete', [ReturnController::class, 'markReturned'])->name('returns.complete');
            // ShipMojo return
            Route::post('returns/{returnRequest}/shipmojo/push', [ShipMojoController::class, 'pushReturnOrder'])->name('returns.shipmojo.push');
        });

        Route::middleware('permission:manage-shipments')->group(function () {
            Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
            Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
            Route::post('shipments/{shipment}/tracking', [ShipmentController::class, 'addTrackingEvent'])->name('shipments.tracking');
        });

        Route::middleware('permission:manage-customers')->group(function () {
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{user}', [CustomerController::class, 'show'])->name('customers.show');
            Route::post('customers/{user}/toggle', [CustomerController::class, 'toggleStatus'])->name('customers.toggle');
        });

        Route::resource('coupons', CouponController::class)->except(['show'])->middleware('permission:manage-coupons');

        Route::middleware('permission:manage-reviews')->group(function () {
            Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
            Route::post('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
            Route::post('reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
            Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
        });

        Route::resource('banners', BannerController::class)->except(['show'])->middleware('permission:manage-banners');
        Route::resource('media', MediaController::class)->only(['index', 'store', 'destroy'])->parameters(['media' => 'media'])->middleware('permission:manage-media');
        Route::resource('blogs', BlogController::class)->except(['show'])->middleware('permission:manage-blogs');
        Route::resource('pages', PageController::class)->except(['show'])->middleware('permission:manage-pages');
        Route::resource('faqs', FaqController::class)->except(['show'])->middleware('permission:manage-faqs');
        Route::middleware('permission:manage-newsletters')->group(function () {
            Route::get('newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');
            Route::delete('newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');
        });

        Route::resource('admins', AdminUserController::class)->except(['show'])->middleware('permission:manage-admins');
        Route::middleware('permission:manage-roles')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });

        Route::get('activities', [ActivityLogController::class, 'index'])->name('activities.index')->middleware('permission:manage-activities');

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

        // ShipMojo utilities (requires admin auth, no extra permission)
        Route::get('shipmojo/ping', [ShipMojoController::class, 'ping'])->name('shipmojo.ping');
        Route::get('shipmojo/warehouses', [ShipMojoController::class, 'warehouses'])->name('shipmojo.warehouses');
        Route::post('shipmojo/rates', [ShipMojoController::class, 'rates'])->name('shipmojo.rates');
    });
});
