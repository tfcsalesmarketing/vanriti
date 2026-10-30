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
