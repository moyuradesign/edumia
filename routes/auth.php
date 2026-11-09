<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Admin\System\MailDeliveryController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:8,1')->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1')->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/account/preferences', [ProfileController::class, 'edit'])->name('account.preferences.edit');
    Route::put('/account/preferences', [ProfileController::class, 'update'])->middleware('throttle:8,1')->name('account.preferences.update');

    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
    Route::get('/account/notifications', [NotificationController::class, 'index'])->name('account.notifications.index');
    Route::patch('/account/notifications/read-all', [NotificationController::class, 'readAll'])
        ->middleware('throttle:20,1')->name('account.notifications.read-all');
    Route::patch('/account/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->middleware('throttle:30,1')->name('account.notifications.read');
    Route::patch('/account/notifications/{notification}/unread', [NotificationController::class, 'unread'])
        ->middleware('throttle:30,1')->name('account.notifications.unread');
    Route::get('/account/notifications/preferences', [NotificationController::class, 'preferences'])
        ->name('account.notifications.preferences.edit');
    Route::put('/account/notifications/preferences', [NotificationController::class, 'updatePreferences'])
        ->middleware('throttle:10,1')->name('account.notifications.preferences.update');
    Route::get('/admin/notifications/delivery', [MailDeliveryController::class, 'index'])
        ->middleware('role:admin')->name('admin.notifications.delivery');
    Route::post('/admin/notifications/delivery/test', [MailDeliveryController::class, 'test'])
        ->middleware(['role:admin', 'throttle:2,1'])->name('admin.notifications.delivery.test');
});
