<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DadiController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\WishlistController;
use App\Models\Page;
use Illuminate\Support\Facades\Route;

// ---------- Sitemap ----------
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// ---------- Robots.txt ----------
Route::get('/robots.txt', fn () => response(
    "User-agent: *\n".
    "Allow: /\n".
    "Disallow: /admin\n".
    "Disallow: /checkout\n".
    "Disallow: /account\n".
    "Disallow: /cart\n".
    "Disallow: /wishlist\n".
    "Disallow: /login\n".
    "Disallow: /register\n".
    "Disallow: /forgot-password\n".
    "Disallow: /reset-password\n\n".
    'Sitemap: '.url('/sitemap.xml'),
    200,
    ['Content-Type' => 'text/plain']
))->name('robots');

// ---------- Home ----------
Route::get('/', [HomeController::class, 'index'])->name('home');

// ---------- Catalogue ----------
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/category/{category:slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('product.show');

// ---------- Cart ----------
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product:slug}', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/{cartItem}/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/{cartItem}/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/apply-coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// ---------- Wishlist ----------
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist/toggle/{product:slug}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

// ---------- Dadi ----------
Route::get('/dadi', [DadiController::class, 'show'])->name('dadi.index');
Route::post('/dadi/onboarding', [DadiController::class, 'onboard'])->name('dadi.onboarding')->middleware('throttle:20,1');
Route::post('/dadi/message', [DadiController::class, 'message'])->name('dadi.message')->middleware('throttle:20,1');
Route::post('/dadi/recommendation/click', [DadiController::class, 'trackClick'])->name('dadi.recommendation.click')->middleware('throttle:30,1');

// ---------- Auth ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit')->middleware('throttle:5,60');
    Route::post('/auth/validate', [AuthController::class, 'validateFields'])->name('auth.validate');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,10');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.store');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------- Checkout (requires login; guest carts merge on login) ----------
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/validate', [CheckoutController::class, 'validateFields'])->name('checkout.validate');
    Route::post('/checkout/verify', [CheckoutController::class, 'verify'])->name('checkout.verify');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/failed/{order}', [CheckoutController::class, 'failed'])->name('checkout.failed');
    Route::get('/checkout/pending/{order}', [CheckoutController::class, 'pending'])->name('checkout.pending');

    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/account/orders/{order}', [AccountController::class, 'orderShow'])->name('account.order');
    Route::post('/account/orders/{order}/cancel', [AccountController::class, 'cancelOrder'])->name('account.order.cancel');
    Route::post('/account/orders/{order}/return', [AccountController::class, 'createReturn'])->name('account.order.return');
    Route::post('/account/orders/{order}/review', [AccountController::class, 'submitReview'])->name('account.order.review');

    Route::get('/account/addresses', [AccountController::class, 'addresses'])->name('account.addresses');
    Route::get('/account/addresses/create', [AccountController::class, 'createAddress'])->name('account.addresses.create');
    Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::get('/account/addresses/{address}/edit', [AccountController::class, 'editAddress'])->name('account.addresses.edit');
    Route::put('/account/addresses/{address}', [AccountController::class, 'updateAddress'])->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
    Route::post('/account/addresses/{address}/default', [AccountController::class, 'makeDefaultAddress'])->name('account.addresses.default');

    Route::post('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
});

// ---------- Track order ----------
Route::get('/track', [TrackController::class, 'index'])->name('track');
Route::post('/track', [TrackController::class, 'lookup'])->name('track.lookup');

// ---------- Content ----------
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{blog:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/faqs', [FaqController::class, 'index'])->name('faq.index');
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,10');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:5,10');
Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('page');
Route::get('/shop/return-policy', fn () => view('storefront.page', ['page' => Page::published()->where('slug', 'return-policy')->firstOrFail()]))->name('shop.return-policy');
Route::get('/shop/cancellation-policy', fn () => view('storefront.page', ['page' => Page::published()->where('slug', 'cancellation-policy')->firstOrFail()]))->name('shop.cancellation-policy');
Route::get('/disclaimer', fn () => view('storefront.page', ['page' => Page::published()->where('slug', 'disclaimer')->firstOrFail()]))->name('info.disclaimer');

// ---------- Company / info pages (CMS managed) ----------
Route::get('/about', fn () => view('storefront.page', ['page' => Page::where('slug', 'about-us')->where('status', 'published')->firstOrFail()]))->name('info.about');
Route::get('/privacy-policy', fn () => view('storefront.page', ['page' => Page::where('slug', 'privacy-policy')->where('status', 'published')->firstOrFail()]))->name('info.privacy');
Route::get('/terms', fn () => view('storefront.page', ['page' => Page::where('slug', 'terms-and-conditions')->where('status', 'published')->firstOrFail()]))->name('info.terms');
Route::get('/shipping', fn () => view('storefront.page', ['page' => Page::where('slug', 'shipping-and-delivery')->where('status', 'published')->firstOrFail()]))->name('info.shipping');
