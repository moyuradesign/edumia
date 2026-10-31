<?php

declare(strict_types=1);

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Student\EnrollmentController;
use App\Http\Controllers\Student\LearningController;
use App\Http\Controllers\Student\QuizAttemptController;
use App\Http\Controllers\Student\Support\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/support/tickets', [TicketController::class, 'index'])->name('support.tickets.index');
    Route::get('/support/tickets/create', [TicketController::class, 'create'])->name('support.tickets.create');
    Route::post('/support/tickets', [TicketController::class, 'store'])->middleware('throttle:6,1')->name('support.tickets.store');
    Route::get('/support/tickets/{ticket}', [TicketController::class, 'show'])->name('support.tickets.show');
    Route::post('/support/tickets/{ticket}/reply', [TicketController::class, 'reply'])->middleware('throttle:12,1')->name('support.tickets.reply');

    // All roles share this entrypoint; DashboardController chooses the workspace.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'store'])->middleware('throttle:12,1')->name('enrollments.store');

    Route::get('/learn/{course}', [LearningController::class, 'course'])->name('learning.course');
    Route::get('/learn/{course}/lessons/{lesson}', [LearningController::class, 'lesson'])->name('learning.lesson');
    Route::post('/learn/{course}/lessons/{lesson}/complete', [LearningController::class, 'complete'])->name('learning.complete');

    Route::get('/learn/quizzes/{quiz}', [QuizAttemptController::class, 'show'])->name('learning.quiz');
    Route::post('/learn/quizzes/{quiz}', [QuizAttemptController::class, 'submit'])->middleware('throttle:10,1')->name('learning.quiz.submit');

    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificate.show');
});
