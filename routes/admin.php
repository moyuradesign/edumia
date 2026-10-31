<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Users\MemberController;
use App\Http\Controllers\Admin\Courses\CategoryController;
use App\Http\Controllers\Admin\Content\MediaController;
use App\Http\Controllers\Admin\System\GeneralSettingsController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Instructor\CourseManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::put('/users/{user}', MemberController::class)->name('users.update');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/settings/general', [GeneralSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/general', [GeneralSettingsController::class, 'update'])->name('settings.update');

        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::get('/media/{media}/download', [MediaController::class, 'download'])->name('media.download');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

        Route::post('/courses/{course}/publish', [CourseManagementController::class, 'publish'])->name('courses.publish');
        Route::post('/courses/{course}/unpublish', [CourseManagementController::class, 'unpublish'])->name('courses.unpublish');
        Route::post('/certificates/{certificate}/revoke', [CertificateController::class, 'revoke'])->name('certificates.revoke');
    });
