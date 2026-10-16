<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\PraiseController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->name('auth.callback');
    Route::post('/dev-login', [LoginController::class, 'devLogin'])->name('dev-login');
});

Route::get('/locale/{locale}', LocaleController::class)->name('locale');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/people', [PeopleController::class, 'index'])->name('people.index');
    Route::get('/people/{person}', [PeopleController::class, 'show'])->name('people.show');

    Route::post('/people/{person}/reviews', [ReviewController::class, 'start'])->name('reviews.start');
    Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::post('/reviews/{review}/share', [ReviewController::class, 'share'])->name('reviews.share');
    Route::post('/reviews/{review}/acknowledge', [ReviewController::class, 'acknowledge'])->name('reviews.acknowledge');

    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    Route::get('/praise', [PraiseController::class, 'index'])->name('praise.index');
    Route::post('/praise', [PraiseController::class, 'store'])->name('praise.store');

    Route::middleware('role:hr,director,admin')->group(function () {
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/audit/export', [AuditLogController::class, 'export'])->name('audit.export');
    });
});
