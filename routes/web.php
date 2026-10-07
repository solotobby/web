<?php

use App\Http\Controllers\CheckoutReturnController;
use App\Http\Controllers\CreatorLoginController;
use App\Http\Controllers\OpenGraphController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::livewire('/', 'home-page')->name('home');
Route::livewire('/explore', 'explore-page')->name('explore');
Route::livewire('/seal', 'seal-page')->name('seal');
Route::livewire('/timeline', 'timeline-page')->name('timeline');
Route::livewire('/timeline/{year}', 'timeline-page')
    ->where('year', '[0-9]{4}')
    ->name('timeline.year');
Route::livewire('/timeline/{day}', 'timeline-page')
    ->where('day', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
    ->name('timeline.day');
Route::livewire('/m/{postcard}', 'message-page')->name('message');
Route::livewire('/creators', 'creators-page')->name('creators');
Route::livewire('/creators/join', 'creator-join')->name('creators.join');
Route::livewire('/creators/access', 'creator-access')->name('creators.access');
Route::get('/creators/login/{token}', CreatorLoginController::class)
    ->where('token', '[A-Za-z0-9]+')
    ->name('creators.login');
Route::livewire('/creators/onboard', 'creator-onboard')->name('creators.onboard');
Route::livewire('/creators/studio', 'creator-studio')->name('creators.studio');
Route::livewire('/with/{slug}', 'creator-door')->name('with');
Route::livewire('/nominate', 'nominate-page')->name('nominate');

Route::get('/checkout/return', CheckoutReturnController::class)->name('checkout.return');
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

// Legal, Trust & Security Routes
Route::livewire('/privacy', 'privacy-page')->name('privacy');
Route::livewire('/terms', 'terms-page')->name('terms');
Route::livewire('/data-retention', 'data-retention-page')->name('data-retention');
Route::livewire('/data-deletion', 'data-deletion-page')->name('data-deletion');
Route::livewire('/security', 'security-page')->name('security');

Route::get('/og/postcard/{postcard}.png', [OpenGraphController::class, 'postcard'])->name('og.postcard');
Route::get('/og/creator/{slug}.png', [OpenGraphController::class, 'creator'])->name('og.creator');
Route::get('/og/cover.png', [OpenGraphController::class, 'cover'])->name('og.cover');

// Executive Admin Console Routes
Route::get('/admin/login', [\App\Http\Controllers\AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [\App\Http\Controllers\AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [\App\Http\Controllers\AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware('admin')->prefix('admin')->group(function () {
    Route::livewire('/', 'admin-dashboard')->name('admin.dashboard');
    Route::get('/prospects', function () {
        return redirect()->route('admin.dashboard', ['tab' => 'prospects']);
    })->name('admin.prospects');
});

