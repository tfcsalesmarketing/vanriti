<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tfc\HomeController;
use App\Http\Controllers\Tfc\ProductController;
use App\Http\Controllers\Tfc\DirectSellingController;
use App\Http\Controllers\Tfc\PageController;
use App\Http\Controllers\Tfc\BlogController;
use App\Http\Controllers\Tfc\ContactController;
use App\Http\Controllers\Tfc\GrievanceController;
use App\Http\Controllers\Tfc\SitemapController;

// 1. Core Corporate Pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about-us', [HomeController::class, 'about'])->name('about');
Route::get('/vanriti-brand', [HomeController::class, 'brand'])->name('brand');
Route::get('/faqs', [HomeController::class, 'faqs'])->name('faqs');

// 2. Direct Selling & Incentive Business Plan Modules
Route::get('/direct-selling', [DirectSellingController::class, 'directSelling'])->name('direct-selling');
Route::get('/incentive-program', [DirectSellingController::class, 'incentiveProgram'])->name('incentive-program');
Route::get('/opportunity', [DirectSellingController::class, 'incentiveProgram'])->name('opportunity');
Route::get('/how-it-works', [DirectSellingController::class, 'howItWorks'])->name('how-it-works');
Route::get('/become-a-seller', [DirectSellingController::class, 'apply'])->name('become-a-seller');
Route::post('/become-a-seller', [DirectSellingController::class, 'submitApplication'])->name('become-a-seller.submit')->middleware('throttle:10,1');
Route::get('/benefits', [DirectSellingController::class, 'benefits'])->name('benefits');

// 3. Product & Category Showcase (Synced live from VANRITI catalogue)
Route::get('/products', [ProductController::class, 'index'])->name('products');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/category/{slug}', [ProductController::class, 'category'])->name('category');

// 4. Corporate Blog & News Updates
Route::get('/blogs', [BlogController::class, 'index'])->name('blogs');
Route::get('/blogs/{slug}', [BlogController::class, 'show'])->name('blogs.show');

// 5. Contact & Business Inquiry
Route::get('/contact-us', [ContactController::class, 'index'])->name('contact');
Route::post('/contact-us', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:10,1');

// 6. Consumer Grievance Redressal (Rule 7, Direct Selling Rules 2021)
Route::get('/grievance', [GrievanceController::class, 'index'])->name('grievance');
Route::post('/grievance', [GrievanceController::class, 'store'])->name('grievance.store')->middleware('throttle:10,1');
Route::get('/grievance/track', [GrievanceController::class, 'track'])->name('grievance.track');
Route::post('/grievance/track', [GrievanceController::class, 'trackStatus'])->name('grievance.track.status')->middleware('throttle:20,1');

// 7. Statutory & Legal Policies (Includes draft warning if unverified)
Route::get('/policy/{slug}', [PageController::class, 'show'])->name('policy');

// 8. XML Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// ============================================================================
// 9. TFC SALES & MARKETING — MEMBER AUTHENTICATION & REGISTRATION
// ============================================================================
use App\Http\Controllers\Tfc\Member\MemberAuthController;
use App\Http\Controllers\Tfc\Member\MemberDashboardController;
use App\Http\Controllers\Tfc\Member\MemberProfileController;
use App\Http\Controllers\Tfc\Member\MemberTeamController;
use App\Http\Controllers\Tfc\Member\MemberKycController;
use App\Http\Controllers\Tfc\Member\KycDocumentController;

Route::prefix('member')->name('member.')->group(function () {
    // Member Registration & Referral
    Route::get('/register', [MemberAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [MemberAuthController::class, 'register'])->name('register.submit')->middleware('throttle:10,1');
    // Live Server-Side Verification AJAX Endpoints (supports both GET and POST)
    Route::match(['GET', 'POST'], '/ajax/verify-sponsor', [MemberAuthController::class, 'verifySponsorAjax'])->name('ajax.verify-sponsor');
    Route::match(['GET', 'POST'], '/ajax/verify-placement', [MemberAuthController::class, 'verifyPlacementAjax'])->name('ajax.verify-placement');
    Route::match(['GET', 'POST'], '/ajax/check-mobile', [MemberAuthController::class, 'checkMobileAjax'])->name('ajax.check-mobile');
    Route::match(['GET', 'POST'], '/ajax/check-login', [MemberAuthController::class, 'checkLoginAjax'])->name('ajax.check-login');

    // Member Login & Logout
    Route::get('/login', [MemberAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [MemberAuthController::class, 'login'])->name('login.submit')->middleware('throttle:10,1');
    Route::post('/logout', [MemberAuthController::class, 'logout'])->name('logout');

    // Secure Expiring WhatsApp Password Setup
    Route::get('/password-setup', [MemberAuthController::class, 'showPasswordSetup'])->name('password-setup');
    Route::post('/password-setup', [MemberAuthController::class, 'savePasswordSetup'])->name('password-setup.submit');

    // Mobile Number Verification & OTP Flow
    Route::get('/verify-mobile', [MemberAuthController::class, 'showVerifyMobile'])->name('verify-mobile');
    Route::post('/verify-mobile/resend', [MemberAuthController::class, 'resendVerifyMobileOtp'])->name('verify-mobile.resend')->middleware('throttle:6,1');
    Route::post('/verify-mobile', [MemberAuthController::class, 'verifyMobileOtpSubmit'])->name('verify-mobile.submit')->middleware('throttle:10,1');

    // Set New Password after Mobile Verification
    Route::get('/set-password', [MemberAuthController::class, 'showSetPassword'])->name('set-password');
    Route::post('/set-password', [MemberAuthController::class, 'saveSetPassword'])->name('set-password.submit')->middleware('throttle:10,1');

    // ========================================================================
    // Member Protected Portal
    // ========================================================================
    Route::middleware(['auth', 'tfc.member'])->prefix('portal')->group(function () {
        Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');

        // Profile & Security
        Route::get('/profile', [MemberProfileController::class, 'show'])->name('profile');
        Route::post('/profile', [MemberProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/password', [MemberProfileController::class, 'updatePassword'])->name('profile.password');

        // Team Genealogy & Lists
        Route::get('/team/tree', [MemberTeamController::class, 'tree'])->name('team.tree');
        Route::get('/team/ajax-node/{id}', [MemberTeamController::class, 'ajaxNode'])->name('team.ajax-node');
        Route::get('/team/direct', [MemberTeamController::class, 'directReferrals'])->name('team.direct');
        Route::get('/team/group/{leg}', [MemberTeamController::class, 'groupLeg'])->name('team.group');

        // KYC Management & Confidential Document Streaming
        Route::get('/kyc', [MemberKycController::class, 'index'])->name('kyc.index');
        Route::post('/kyc', [MemberKycController::class, 'store'])->name('kyc.store');
        Route::get('/kyc/document/{id}/{type}', [KycDocumentController::class, 'view'])->name('kyc.document');
    });
});

