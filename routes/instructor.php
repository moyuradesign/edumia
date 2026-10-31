<?php

declare(strict_types=1);

use App\Http\Controllers\Instructor\CourseManagementController;
use App\Http\Controllers\Instructor\AssignmentController;
use App\Http\Controllers\Instructor\EbookController;
use App\Http\Controllers\Instructor\LiveClassController;
use App\Http\Controllers\Instructor\BootcampController;
use App\Http\Controllers\Instructor\ReportsController;
use App\Http\Controllers\Instructor\EarningsController;
use App\Http\Controllers\Instructor\LessonManagementController;
use App\Http\Controllers\Instructor\QuizManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:instructor,admin'])
    ->prefix('instructor')
    ->name('instructor.')
    ->group(function (): void {
        Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings.index');
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');

        Route::get('/bootcamps', [BootcampController::class, 'index'])->name('bootcamps.index');
        Route::get('/bootcamps/create', [BootcampController::class, 'create'])->name('bootcamps.create');
        Route::post('/bootcamps', [BootcampController::class, 'store'])->name('bootcamps.store');
        Route::get('/bootcamps/{bootcamp}/edit', [BootcampController::class, 'edit'])->name('bootcamps.edit');
        Route::put('/bootcamps/{bootcamp}', [BootcampController::class, 'update'])->name('bootcamps.update');
        Route::post('/bootcamps/{bootcamp}/modules', [BootcampController::class, 'module'])->name('bootcamps.modules.store');
        Route::post('/bootcamps/{bootcamp}/modules/{module}/resources', [BootcampController::class, 'resource'])->name('bootcamps.resources.store');
        Route::post('/bootcamps/{bootcamp}/sessions', [BootcampController::class, 'session'])->name('bootcamps.sessions.store');

        Route::get('/ebooks', [EbookController::class, 'index'])->name('ebooks.index');
        Route::get('/ebooks/create', [EbookController::class, 'create'])->name('ebooks.create');
        Route::post('/ebooks', [EbookController::class, 'store'])->name('ebooks.store');
        Route::get('/ebooks/{ebook}/edit', [EbookController::class, 'edit'])->name('ebooks.edit');
        Route::put('/ebooks/{ebook}', [EbookController::class, 'update'])->name('ebooks.update');

        Route::get('/courses/create', [CourseManagementController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CourseManagementController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}/edit', [CourseManagementController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [CourseManagementController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [CourseManagementController::class, 'destroy'])->name('courses.destroy');
        Route::post('/courses/{course}/submit', [CourseManagementController::class, 'submit'])->name('courses.submit');

        Route::get('/courses/{course}/live-classes', [LiveClassController::class, 'index'])->name('live-classes.index');
        Route::post('/courses/{course}/live-classes', [LiveClassController::class, 'store'])->name('live-classes.store');
        Route::put('/courses/{course}/live-classes/{liveClass}', [LiveClassController::class, 'update'])->name('live-classes.update');

        Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::put('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::get('/courses/{course}/assignments/{assignment}/gradebook', [AssignmentController::class, 'gradebook'])->name('assignments.gradebook');
        Route::put('/courses/{course}/assignments/{assignment}/submissions/{submission}/grade', [AssignmentController::class, 'grade'])->name('assignments.grade');

        Route::post('/courses/{course}/sections', [LessonManagementController::class, 'section'])->name('sections.store');
        Route::post('/courses/{course}/sections/{section}/lessons', [LessonManagementController::class, 'lesson'])->name('lessons.store');
        Route::put('/courses/{course}/lessons/{lesson}', [LessonManagementController::class, 'updateLesson'])->name('lessons.update');

        Route::post('/courses/{course}/quizzes', [QuizManagementController::class, 'store'])->name('quizzes.store');
        Route::post('/courses/{course}/quizzes/{quiz}/questions', [QuizManagementController::class, 'question'])->name('questions.store');
        Route::post('/courses/{course}/quizzes/{quiz}/publish', [QuizManagementController::class, 'publish'])->name('quizzes.publish');
    });
