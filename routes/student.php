<?php

declare(strict_types=1);

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Student\EnrollmentController;
use App\Http\Controllers\Student\CheckoutController;
use App\Http\Controllers\Student\InstructorApplicationController;
use App\Http\Controllers\Student\AssignmentController;
use App\Http\Controllers\Student\CourseWishlistController;
use App\Http\Controllers\Student\CourseReviewController;
use App\Http\Controllers\Student\EbookLibraryController;
use App\Http\Controllers\Student\LiveClassController;
use App\Http\Controllers\Student\CourseForumController;
use App\Http\Controllers\Student\BootcampEnrollmentController;
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

    Route::get('/instructor-application', [InstructorApplicationController::class, 'index'])->name('instructor-application.index');
    Route::post('/instructor-application', [InstructorApplicationController::class, 'store'])->middleware('throttle:3,1')->name('instructor-application.store');

    // All roles share this entrypoint; DashboardController chooses the workspace.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/my-bootcamps', [BootcampEnrollmentController::class, 'index'])->name('bootcamps.my');
    Route::post('/my-bootcamps/{bootcamp}', [BootcampEnrollmentController::class, 'enroll'])
        ->middleware('throttle:12,1')->name('bootcamps.enroll');
    Route::get('/my-bootcamps/{bootcamp}/classroom', [BootcampEnrollmentController::class, 'classroom'])->name('bootcamps.classroom');

    Route::get('/my-ebooks', [EbookLibraryController::class, 'index'])->name('ebooks.library');
    Route::post('/my-ebooks/{ebook}', [EbookLibraryController::class, 'claim'])->middleware('throttle:10,1')->name('ebooks.claim');
    Route::get('/my-ebooks/{ebook}/download', [EbookLibraryController::class, 'download'])->name('ebooks.download');

    Route::get('/saved-courses', [CourseWishlistController::class, 'index'])->name('saved-courses.index');
    Route::post('/saved-courses/{course}', [CourseWishlistController::class, 'store'])->name('saved-courses.store');
    Route::delete('/saved-courses/{course}', [CourseWishlistController::class, 'destroy'])->name('saved-courses.destroy');
    Route::post('/courses/{course}/reviews', [CourseReviewController::class, 'store'])
        ->middleware('throttle:5,1')->name('course-reviews.store');

    Route::get('/checkout/orders', [CheckoutController::class, 'index'])->name('checkout.orders.index');
    Route::post('/checkout/courses/{course}', [CheckoutController::class, 'store'])->middleware('throttle:6,1')->name('checkout.orders.store');
    Route::get('/checkout/orders/{order}', [CheckoutController::class, 'show'])->name('checkout.orders.show');
    Route::patch('/checkout/orders/{order}/cancel', [CheckoutController::class, 'cancel'])->middleware('throttle:6,1')->name('checkout.orders.cancel');

    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'store'])->middleware('throttle:12,1')->name('enrollments.store');

    Route::get('/learn/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('learning.assignments.show');
    Route::post('/learn/{course}/assignments/{assignment}', [AssignmentController::class, 'submit'])->middleware('throttle:8,1')->name('learning.assignments.submit');

    Route::get('/learn/{course}/forums', [CourseForumController::class, 'index'])->name('learning.forums.index');
    Route::post('/learn/{course}/forums', [CourseForumController::class, 'store'])->middleware('throttle:5,1')->name('learning.forums.store');
    Route::get('/learn/{course}/forums/{forum}', [CourseForumController::class, 'show'])->name('learning.forums.show');
    Route::post('/learn/{course}/forums/{forum}/replies', [CourseForumController::class, 'reply'])
        ->middleware('throttle:12,1')->name('learning.forums.reply');

    Route::get('/learn/{course}/live-classes', [LiveClassController::class, 'index'])->name('learning.live-classes.index');

    Route::get('/learn/{course}', [LearningController::class, 'course'])->name('learning.course');
    Route::get('/learn/{course}/lessons/{lesson}', [LearningController::class, 'lesson'])->name('learning.lesson');
    Route::post('/learn/{course}/lessons/{lesson}/complete', [LearningController::class, 'complete'])->name('learning.complete');

    Route::get('/learn/quizzes/{quiz}', [QuizAttemptController::class, 'show'])->name('learning.quiz');
    Route::post('/learn/quizzes/{quiz}', [QuizAttemptController::class, 'submit'])->middleware('throttle:10,1')->name('learning.quiz.submit');

    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificate.show');
});
