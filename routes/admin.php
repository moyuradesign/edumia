<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Users\MemberController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Instructor\CourseManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::put('/users/{user}', MemberController::class)->name('users.update');
        Route::post('/courses/{course}/publish', [CourseManagementController::class, 'publish'])->name('courses.publish');
        Route::post('/courses/{course}/unpublish', [CourseManagementController::class, 'unpublish'])->name('courses.unpublish');
        Route::post('/certificates/{certificate}/revoke', [CertificateController::class, 'revoke'])->name('certificates.revoke');
    });
