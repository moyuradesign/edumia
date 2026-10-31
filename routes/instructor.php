<?php

declare(strict_types=1);

use App\Http\Controllers\Instructor\CourseManagementController;
use App\Http\Controllers\Instructor\LessonManagementController;
use App\Http\Controllers\Instructor\QuizManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:instructor,admin'])
    ->prefix('instructor')
    ->name('instructor.')
    ->group(function (): void {
        Route::get('/courses/create', [CourseManagementController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CourseManagementController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}/edit', [CourseManagementController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [CourseManagementController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [CourseManagementController::class, 'destroy'])->name('courses.destroy');
        Route::post('/courses/{course}/submit', [CourseManagementController::class, 'submit'])->name('courses.submit');

        Route::post('/courses/{course}/sections', [LessonManagementController::class, 'section'])->name('sections.store');
        Route::post('/courses/{course}/sections/{section}/lessons', [LessonManagementController::class, 'lesson'])->name('lessons.store');
        Route::put('/courses/{course}/lessons/{lesson}', [LessonManagementController::class, 'updateLesson'])->name('lessons.update');

        Route::post('/courses/{course}/quizzes', [QuizManagementController::class, 'store'])->name('quizzes.store');
        Route::post('/courses/{course}/quizzes/{quiz}/questions', [QuizManagementController::class, 'question'])->name('questions.store');
        Route::post('/courses/{course}/quizzes/{quiz}/publish', [QuizManagementController::class, 'publish'])->name('quizzes.publish');
    });
